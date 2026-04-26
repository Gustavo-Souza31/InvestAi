#!/usr/bin/env python3
"""
InvestAI — Analisador de Notícias com Gemini AI
Recebe via stdin JSON com {noticias, perfil} e retorna análise de impacto
nas finanças pessoais do usuário com sugestões de investimento e economia.
"""
import sys
import json

# Importa configuração da chave
sys.path.insert(0, __file__.rsplit("/", 1)[0])
from config import get_gemini_key

def montar_prompt(noticias: list, perfil: dict) -> str:
    """Monta o prompt estruturado para a Gemini."""
    noticias_texto = ""
    for i, n in enumerate(noticias[:10], 1):
        noticias_texto += f"\n{i}. [{n.get('fonte', '')}] {n.get('titulo', '')}\n   Resumo: {n.get('resumo', '')[:200]}\n"

    saldo = perfil.get("saldo_atual", 0)
    ganhos = perfil.get("total_ganhos", 0)
    despesas = perfil.get("total_despesas", 0)
    renda = perfil.get("renda_mensal", 0)
    objetivo = perfil.get("objetivo", "Não informado")
    categorias = perfil.get("categorias_despesas", [])

    categorias_str = ", ".join(categorias) if categorias else "não informadas"

    prompt = f"""Você é o Arquiteto Financeiro do InvestAI. Analise as notícias econômicas abaixo e gere um relatório de impacto PERSONALIZADO para o perfil financeiro deste usuário.

PERFIL DO USUÁRIO:
- Saldo atual: R$ {saldo:,.2f}
- Renda mensal: R$ {renda:,.2f}
- Total de ganhos registrados: R$ {ganhos:,.2f}
- Total de despesas registradas: R$ {despesas:,.2f}
- Objetivo financeiro: {objetivo}
- Principais categorias de gasto: {categorias_str}

NOTÍCIAS DO DIA:
{noticias_texto}

TAREFA:
Responda APENAS com JSON puro (sem markdown), seguindo exatamente esta estrutura:
{{
  "resumo_geral": "2-3 frases contextualizando o cenário econômico atual e o impacto no perfil do usuário",
  "nivel_alerta": "baixo|medio|alto",
  "analises": [
    {{
      "titulo_noticia": "título exato da notícia",
      "impacto": "alto|medio|baixo",
      "como_afeta": "1 frase direta explicando como afeta o orçamento/investimentos deste usuário específico",
      "sugestao_investimento": "1 ação concreta de investimento que o usuário pode tomar agora",
      "dica_economia": "1 dica prática para economizar ou proteger o orçamento diante desta notícia"
    }}
  ],
  "top_acao_da_semana": "A única coisa mais importante que o usuário deve fazer esta semana com base nas notícias"
}}

Seja direto, pragmático e personalizado. Foque nas notícias mais relevantes para o perfil."""

    return prompt


def analisar_com_gemini(noticias: list, perfil: dict) -> dict:
    """Chama a Gemini API e retorna a análise estruturada."""
    key = get_gemini_key()

    if not key:
        return {
            "status": "sem_chave",
            "mensagem": "Chave Gemini API não configurada. Adicione GEMINI_API_KEY no arquivo .env na raiz do projeto.",
            "resumo_geral": "Configure a chave da API Gemini para ativar a análise de IA personalizada.",
            "nivel_alerta": "baixo",
            "analises": [],
            "top_acao_da_semana": "Configure sua chave Gemini API para receber recomendações personalizadas."
        }

    try:
        from google import genai
        client = genai.Client(api_key=key)

        prompt = montar_prompt(noticias, perfil)

        response = client.models.generate_content(
            model="gemini-2.0-flash",
            contents=prompt,
        )

        raw = response.text.strip()

        # Remove blocos de markdown caso existam
        if raw.startswith("```"):
            raw = raw.split("```")[1]
            if raw.startswith("json"):
                raw = raw[4:]
        raw = raw.strip()

        resultado = json.loads(raw)
        resultado["status"] = "ok"
        return resultado

    except json.JSONDecodeError as e:
        return {
            "status": "error",
            "mensagem": f"Erro ao parsear resposta da IA: {str(e)}",
            "raw": raw if 'raw' in dir() else ""
        }
    except Exception as e:
        return {
            "status": "error",
            "mensagem": f"Erro na chamada da Gemini API: {str(e)}"
        }


if __name__ == "__main__":
    try:
        # Lê o JSON passado via stdin
        entrada_raw = sys.stdin.read().strip()
        if not entrada_raw:
            raise ValueError("Nenhum dado recebido via stdin.")

        entrada = json.loads(entrada_raw)
        noticias = entrada.get("noticias", [])
        perfil = entrada.get("perfil", {})

        resultado = analisar_com_gemini(noticias, perfil)
        print(json.dumps(resultado, ensure_ascii=False))

    except Exception as e:
        print(json.dumps({
            "status": "error",
            "mensagem": str(e)
        }))
        sys.exit(1)
