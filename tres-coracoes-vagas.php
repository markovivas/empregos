<?php
/**
 * Plugin Name: Vagas Três Corações
 * Description: Importa e exibe automaticamente as vagas do site da Prefeitura de Três Corações.
 * Version: 1.0
 * Author: Marco Antonio Vivas
 */

add_shortcode('vagas_tres_coracoes', 'vtc_exibir_vagas');

function vtc_enqueue_styles() {
    wp_register_style('tc-vagas-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_style('tc-vagas-style');
}
add_action('wp_enqueue_scripts', 'vtc_enqueue_styles');

function vtc_exibir_vagas() {
    $todas_vagas = [];

    for ($pagina = 1; $pagina <= 10; $pagina++) {
        $html = wp_remote_get("https://trescoracoes.mg.gov.br/empregatrescoracoes/vagas?&pagina={$pagina}");

        if (is_wp_error($html)) break;

        $body = wp_remote_retrieve_body($html);

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($body);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $items = $xpath->query('//div[contains(@class, "home-painel__listagem-item")]');

        if ($items->length === 0) break;

        foreach ($items as $item) {
            $vaga_node = $xpath->query('.//h2', $item);
            $cidade_node = $xpath->query('.//div[contains(@class,"subtitulos")]/span[2]', $item);
            $quantidade_node = $xpath->query('.//div[contains(@class,"descricao")]/span', $item);

            $vaga = $vaga_node->length ? trim($vaga_node[0]->textContent) : '';
            $cidade = $cidade_node->length ? trim($cidade_node[0]->textContent) : '';
            $quantidade = $quantidade_node->length ? trim($quantidade_node[0]->textContent) : '';

            // Verifica se a vaga não está vazia antes de adicionar
            if (!empty($vaga) || !empty($cidade) || !empty($quantidade)) {
                $todas_vagas[] = [
                    'vaga' => $vaga,
                    'cidade' => $cidade,
                    'quantidade' => $quantidade,
                ];
            }
        }
    }

    // Se não houver vagas, retorna mensagem
    if (empty($todas_vagas)) {
        return '<div class="tc-vagas-wrapper"><p>Nenhuma vaga encontrada no momento.</p></div>';
    }

    $output = '<div class="tc-vagas-wrapper"><table class="tc-vagas-table">';
    $output .= '<thead><tr><th>Vaga</th><th>Cidade</th><th>Quantidade</th></tr></thead><tbody>';

    foreach ($todas_vagas as $vaga) {
        // Ignora linhas totalmente vazias (segunda verificação por segurança)
        if (empty(trim($vaga['vaga'])) && empty(trim($vaga['cidade'])) && empty(trim($vaga['quantidade']))) {
            continue;
        }

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