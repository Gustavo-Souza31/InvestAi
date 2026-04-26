<?php
/**
 * backend/services/NewsAggregator.php
 * Serviço de agregação RSS — coleta de múltiplos feeds temáticos de economia.
 */

class NewsAggregator
{
    /** Feeds RSS com metadados visuais — feeds temáticos para mais volume */
    private array $feeds = [
        // InfoMoney — 3 feeds especializados
        [
            'fonte'  => 'InfoMoney',
            'url'    => 'https://www.infomoney.com.br/mercados/feed/',
            'cor'    => '#06b6d4',
            'icone'  => 'bi-currency-dollar',
        ],
        [
            'fonte'  => 'InfoMoney',
            'url'    => 'https://www.infomoney.com.br/economia/feed/',
            'cor'    => '#06b6d4',
            'icone'  => 'bi-currency-dollar',
        ],
        [
            'fonte'  => 'InfoMoney',
            'url'    => 'https://www.infomoney.com.br/onde-investir/feed/',
            'cor'    => '#06b6d4',
            'icone'  => 'bi-currency-dollar',
        ],
        // G1 Economia (retorna gzip — tratado no fetchFeed)
        [
            'fonte'  => 'G1 Economia',
            'url'    => 'https://g1.globo.com/rss/g1/economia/',
            'cor'    => '#ef4444',
            'icone'  => 'bi-globe2',
        ],
        // Investing.com Brasil
        [
            'fonte'  => 'Investing.com',
            'url'    => 'https://br.investing.com/rss/news.rss',
            'cor'    => '#f59e0b',
            'icone'  => 'bi-bar-chart-line',
        ],
    ];

    /** Palavras-chave que DEVEM estar presentes (ao menos 1) */
    private array $palavrasEconomicas = [
        'juros','selic','inflação','ipca','igpm','inpc','pib','banco central','copom',
        'taxa básica','spread','juro real','rendimento',
        'dólar','câmbio','euro','bolsa','ibovespa','ações','b3',
        'mercado financeiro','mercado de capitais','dow jones','nasdaq',
        'investimento','cdi','tesouro direto','renda fixa','renda variável','fundo',
        'fii','fundo imobiliário','lci','lca','poupança','previdência','criptomoeda','bitcoin',
        'crédito','consignado','financiamento','empréstimo','endividamento','inadimplência',
        'salário mínimo','salário','fgts','inss','seguro desemprego',
        'emprego','desemprego','mercado de trabalho','vagas de emprego',
        'combustível','gasolina','diesel','energia elétrica','tarifa','aluguel','custo de vida',
        'reajuste','imposto de renda','receita federal','reforma tributária','imposto',
        'recessão','crescimento econômico','superávit','déficit','dívida pública',
        'orçamento','privatização','exportação','importação','balança comercial',
        'indústria','agronegócio','petrobras','vale','nubank','itaú','bradesco','xp',
        'economia','econômico','econômica','financeiro','financeira','finanças',
        'weg','embraer','localiza','magazine luiza','ambev','gerdau',
        'fed','reserva federal','banco central europeu','taxa de juros americana',
        'commodities','petróleo','minério de ferro','soja','milho','café',
        'tesouro nacional','lft','ntn','debenture','cra','cri',
        'banco','corretora','fintech','pagamento','pix','open finance',
    ];

    /** Títulos claramente fora de escopo — descarte imediato sem exceção */
    private array $hardReject = [
        'onde assistir','ao jogo do','ao jogo da','escalação do','escalação da',
        'placar de','placar do','resultado do jogo','jogo do brasileir',
        'jogo da copa','semifinal da copa','quartas de final da copa','rodada do campeonato',
        'gol de','goleada','hat-trick',
    ];

    /** Termos que descartam se o TÍTULO não tiver palavra econômica */
    private array $blacklist = [
        // Futebol — times brasileiros
        'flamengo','palmeiras','corinthians','são paulo fc','grêmio','fluminense',
        'vasco','botafogo','santos fc','bragantino','atlético mineiro','cruzeiro',
        'sport recife','ceará sc','bahia sc','fortaleza ec','atletico-go',
        // Futebol — geral
        'futebol','gol ','campeonato brasileiro','copa do mundo','libertadores',
        'brasileirão','série a do','série b do',
        'escalação','goleiro','zagueiro','atacante','meia ',
        'técnico demitido','transferência de jogador','contratação de jogador',
        // Outros esportes
        'nba','nfl','mlb','nhl','fórmula 1','f1 ','tênis','basquete','vôlei',
        'atletismo','natação','ciclismo','handebol','rugby',
        'ufc ','mma ','boxe ',
        // Entretenimento
        'bbb','big brother','reality show','novela ','ator ','atriz ',
        'cantor ','cantora ','música nova','clipe ','show musical',
        'festival de música','grammy','oscar ','emmy',
        // Crime sem impacto econômico
        'assassinato','homicídio','baleado','tiroteio','sequestro','latrocínio',
        'tráfico de drogas','preso em flagrante','delegado prende',
        // Clima/Saúde sem impacto econômico
        'dengue','zika','vacina contra gripe','previsão do tempo','chuva forte',
    ];

    /**
     * Coleta e normaliza notícias de todos os feeds.
     * @param int $limitePorFonte Máximo de notícias por fonte
     * @return array Array de notícias normalizadas (sem duplicatas de título)
     */
    public function fetch(int $limitePorFonte = 15): array
    {
        $todas  = [];
        $vistos = []; // deduplicação por título normalizado

        foreach ($this->feeds as $feed) {
            $xml = $this->fetchFeed($feed['url']);
            if (!$xml) continue;

            $items = $xml->channel->item ?? $xml->entry ?? [];
            $count = 0;

            foreach ($items as $item) {
                if ($count >= $limitePorFonte) break;

                $titulo = $this->limparHtml((string)($item->title ?? ''));
                if (empty($titulo)) continue;

                // Deduplicação
                $chave = mb_strtolower(mb_substr($titulo, 0, 60));
                if (isset($vistos[$chave])) continue;
                $vistos[$chave] = true;

                $resumo = $this->limparHtml((string)($item->description ?? $item->summary ?? $item->content ?? ''));
                if (mb_strlen($resumo) > 350) $resumo = mb_substr($resumo, 0, 347) . '...';
                if (empty($resumo)) $resumo = 'Sem resumo disponível.';

                // Filtro econômico obrigatório
                if (!$this->isEconomica($titulo, $resumo)) continue;

                $link = (string)($item->link ?? $item->id ?? '#');
                if ($link === '' && isset($item->link['href'])) {
                    $link = (string)$item->link['href'];
                }

                $dataRaw = (string)($item->pubDate ?? $item->published ?? $item->updated ?? '');
                $dataTs  = $dataRaw ? strtotime($dataRaw) : time();
                $data    = $dataTs ? date('Y-m-d H:i:s', $dataTs) : date('Y-m-d H:i:s');

                $todas[] = [
                    'titulo'      => $titulo,
                    'link'        => $link,
                    'resumo'      => $resumo,
                    'fonte'       => $feed['fonte'],
                    'cor_fonte'   => $feed['cor'],
                    'icone_fonte' => $feed['icone'],
                    'data'        => $data,
                ];
                $count++;
            }
        }

        // Ordenar por data descendente
        usort($todas, fn($a, $b) => strtotime($b['data']) - strtotime($a['data']));

        return $todas;
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function fetchFeed(string $url): ?SimpleXMLElement
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 3,
            CURLOPT_TIMEOUT         => 12,
            CURLOPT_ENCODING        => '',          // aceita gzip, deflate, br
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; InvestAI/2.0; RSS Reader)',
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_HTTPHEADER      => [
                'Accept: application/rss+xml, application/xml, text/xml, */*',
            ],
        ]);
        $xmlStr = curl_exec($ch);
        curl_close($ch);

        if (empty($xmlStr)) return null;

        // Remove namespaces que quebram o SimpleXML
        $xmlStr = preg_replace('/(<\/?)([a-zA-Z]+):/', '$1', $xmlStr);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlStr);
        libxml_clear_errors();

        return $xml ?: null;
    }

    private function limparHtml(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function isEconomica(string $titulo, string $resumo): bool
    {
        $tituloLower = mb_strtolower($titulo);
        $textoTotal  = $tituloLower . ' ' . mb_strtolower($resumo);

        // Hard reject — sem exceção
        foreach ($this->hardReject as $h) {
            if (mb_strpos($tituloLower, $h) !== false) return false;
        }

        // Precisa ter ao menos 1 palavra econômica em qualquer parte do texto
        $temEconomico = false;
        foreach ($this->palavrasEconomicas as $p) {
            if (mb_strpos($textoTotal, $p) !== false) {
                $temEconomico = true;
                break;
            }
        }
        if (!$temEconomico) return false;

        // Se o TÍTULO tem termo da blacklist mas a palavra econômica está APENAS no resumo → rejeitar
        $temEconomicoNoTitulo = false;
        foreach ($this->palavrasEconomicas as $p) {
            if (mb_strpos($tituloLower, $p) !== false) {
                $temEconomicoNoTitulo = true;
                break;
            }
        }

        foreach ($this->blacklist as $b) {
            if (mb_strpos($tituloLower, $b) !== false && !$temEconomicoNoTitulo) {
                return false;
            }
        }

        return true;
    }
}
