<?php
// Função para buscar vagas de todas as páginas
function tc_get_vagas() {
    $base_url = 'https://trescoracoes.mg.gov.br/empregatrescoracoes/vagas?&pagina=';
    $page = 1;
    $vagas = array();
    $max_pages = 15; // Limite de páginas para evitar loop infinito

    while ($page <= $max_pages) {
        $url = $base_url . $page;
        $response = wp_remote_get($url);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            break;
        }

        $body = wp_remote_retrieve_body($response);
        $dom = new DOMDocument();
        @$dom->loadHTML($body);
        $xpath = new DOMXPath($dom);

        // Encontrar a tabela de vagas
        $rows = $xpath->query("//table[contains(@class, 'table')]/tbody/tr");

        if ($rows->length === 0) {
            break; // Não há mais vagas
        }

        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length >= 3) {
                $vaga = trim($cols[0]->textContent);
                $cidade = trim($cols[1]->textContent);
                $quantidade = trim($cols[2]->textContent);
                // Ignora linhas totalmente vazias
                if ($vaga !== '' || $cidade !== '' || $quantidade !== '') {
                    $vagas[] = array(
                        'vaga' => $vaga,
                        'cidade' => $cidade,
                        'quantidade' => $quantidade
                    );
                }
            }
        }

        $page++;
    }

    // Ordenar vagas por nome da vaga
    usort($vagas, function($a, $b) {
        return strcmp($a['vaga'], $b['vaga']);
    });

    return $vagas;
}

// Função para obter o próximo horário de atualização
function tc_get_next_update_time() {
    $horarios = ['00:00', '10:00', '18:00', '22:00'];
    $agora = current_time('H:i');
    $hoje = current_time('Y-m-d');
    foreach ($horarios as $hora) {
        if ($agora < $hora) {
            return strtotime("$hoje $hora:00");
        }
    }
    // Se já passou de 22:00, retorna o próximo dia às 00:00
    return strtotime("tomorrow 00:00:00");
}

// Shortcode para exibir as vagas
function tc_show_vagas_shortcode() {
    $transient_key = 'tc_vagas_cache';
    $vagas = get_transient($transient_key);
    if ($vagas === false) {
        $vagas = tc_get_vagas();
        $next_update = tc_get_next_update_time();
        $expira_em = $next_update - time();
        if ($expira_em < 60) $expira_em = 60; // Garante pelo menos 1 min
        set_transient($transient_key, $vagas, $expira_em);
    }
    if (empty($vagas)) {
        return '<p>Nenhuma vaga encontrada no momento.</p>';
    }

    $output = '<div class="tc-vagas-wrapper"><table class="tc-vagas-table">
        <thead>
            <tr>
                <th>Vaga</th>
                <th>Cidade</th>
                <th>Quantidade</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($vagas as $vaga) {
        // Ignora linhas totalmente vazias ou só com espaços
        if (empty(trim($vaga['vaga'])) && empty(trim($vaga['cidade'])) && empty(trim($vaga['quantidade']))) continue;
        $output .= sprintf(
            '<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
            </tr>',
            esc_html($vaga['vaga']),
            esc_html($vaga['cidade']),
            esc_html($vaga['quantidade'])
        );
    }

    $output .= '</tbody></table></div>';

    return $output;
}
add_shortcode('tc_vagas', 'tc_show_vagas_shortcode');