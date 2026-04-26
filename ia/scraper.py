#!/usr/bin/env python3
"""
InvestAI — Scraper de Notícias Financeiras
Coleta notícias dos feeds RSS de G1 Economia, Valor Econômico e InfoMoney.
Retorna JSON com lista de notícias filtradas por relevância financeira.
"""
import sys
import json
import feedparser
from datetime import datetime
import time

# ─── Feeds RSS ────────────────────────────────────────────────────────────────
FEEDS = [
    {
        "fonte": "G1 Economia",
        "url": "https://g1.globo.com/rss/g1/economia/",
        "cor": "#ef4444",
        "icone": "bi-globe2"
    },
    {
        "fonte": "Valor Econômico",
        "url": "https://valor.globo.com/financas/rss",
        "cor": "#6366f1",
        "icone": "bi-bar-chart-line"
    },
    {
        "fonte": "InfoMoney",
        "url": "https://www.infomoney.com.br/feed/",
        "cor": "#06b6d4",
        "icone": "bi-currency-dollar"
    },
]

# Palavras-chave para filtrar notícias com alto impacto nas finanças pessoais
PALAVRAS_RELEVANTES = [
    "juros", "selic", "inflação", "ipca", "dólar", "câmbio", "poupança",
    "investimento", "bolsa", "ibovespa", "cdi", "tesouro", "renda fixa",
    "previdência", "imposto", "ir", "declaração", "salário", "emprego",
    "desemprego", "custo de vida", "combustível", "gasolina", "energia",
    "tarifa", "aluguel", "crédito", "consignado", "financiamento",
    "mercado", "economia", "pib", "recessão", "crescimento", "bc", "banco central"
]

def formatar_data(entry) -> str:
    """Tenta extrair e formatar a data da entrada do feed."""
    try:
        if hasattr(entry, 'published_parsed') and entry.published_parsed:
            t = time.mktime(entry.published_parsed)
            return datetime.fromtimestamp(t).strftime("%d/%m/%Y %H:%M")
    except Exception:
        pass
    return datetime.now().strftime("%d/%m/%Y %H:%M")

def calcular_relevancia(titulo: str, resumo: str) -> str:
    """Classifica relevância com base nas palavras-chave."""
    texto = (titulo + " " + resumo).lower()
    pontos = sum(1 for p in PALAVRAS_RELEVANTES if p in texto)
    if pontos >= 3:
        return "alto"
    elif pontos >= 1:
        return "medio"
    return "baixo"

def coletar_noticias(limite_por_feed: int = 8) -> list[dict]:
    """Coleta e retorna notícias de todos os feeds."""
    todas = []

    for feed_info in FEEDS:
        try:
            parsed = feedparser.parse(feed_info["url"])
            for entry in parsed.entries[:limite_por_feed]:
                titulo = getattr(entry, 'title', '').strip()
                resumo = getattr(entry, 'summary', '') or getattr(entry, 'description', '')
                # Remove tags HTML simples do resumo
                import re
                resumo = re.sub(r'<[^>]+>', '', resumo).strip()
                resumo = resumo[:300] + "..." if len(resumo) > 300 else resumo

                link = getattr(entry, 'link', '#')
                data = formatar_data(entry)
                relevancia = calcular_relevancia(titulo, resumo)

                if titulo:
                    todas.append({
                        "titulo": titulo,
                        "resumo": resumo or "Sem resumo disponível.",
                        "fonte": feed_info["fonte"],
                        "cor_fonte": feed_info["cor"],
                        "icone_fonte": feed_info["icone"],
                        "url": link,
                        "data": data,
                        "relevancia": relevancia,
                    })
        except Exception as e:
            # Continua para o próximo feed em caso de falha
            sys.stderr.write(f"[scraper] Erro no feed {feed_info['fonte']}: {e}\n")

    # Ordena: alto > medio > baixo, mantendo ordem de chegada dentro de cada grupo
    ordem = {"alto": 0, "medio": 1, "baixo": 2}
    todas.sort(key=lambda n: ordem.get(n["relevancia"], 9))

    return todas[:20]  # Máximo 20 notícias

if __name__ == "__main__":
    try:
        noticias = coletar_noticias()
        print(json.dumps({"status": "ok", "noticias": noticias}, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))
        sys.exit(1)
