<?php
/**
 * Plugin Name: Vagas Três Corações
 * Description: Importa e exibe automaticamente as vagas do site da Prefeitura de Três Corações.
 * Version: 1.0
 * Author: Marco Antonio Vivas
 */

add_shortcode('vagas_tres_coracoes', 'vtc_exibir_vagas');

// Função para enfileirar estilos e scripts
function vtc_enqueue_styles() {
    wp_register_style('tc-vagas-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_style('tc-vagas-style');

    // Registrar e enfileirar o novo script JS
    wp_register_script('tc-fullscreen-script', plugin_dir_url(__FILE__) . 'fullscreen.js', array('jquery'), '1.0', true);
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

    // Enfileirar o script e passar os dados das vagas para o JS
    wp_enqueue_script('tc-fullscreen-script');
    wp_localize_script('tc-fullscreen-script', 'vagasData', array(
        'vagas' => $todas_vagas
    ));

    $output = '<div class="tc-vagas-wrapper">';
    
    // Botão para ativar a tela cheia
    $output .= '<div class="tc-fullscreen-btn-wrapper"><button id="tc-activate-fullscreen" class="tc-fullscreen-btn">Ativar Tela Cheia</button></div>';

    // Tabela de vagas
    $output .= '<table class="tc-vagas-table">';
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

    // Estrutura HTML para o modo tela cheia (inicialmente oculta)
    $output .= '
    <div id="tc-fullscreen-container" style="display: none;">
        <button id="tc-exit-fullscreen" class="tc-exit-fullscreen-btn">&times;</button>
        <div id="tc-fullscreen-main">
            <div id="tc-fullscreen-content">
                <h2 id="tc-vaga-title"></h2>
                <p id="tc-vaga-details"></p>
            </div>
            <div id="tc-progress-bar-container">
                <div id="tc-progress-bar"></div>
            </div>
        </div>
        <div id="tc-fullscreen-sidebar">
            <h3>Outras vagas</h3>
            <table id="tc-next-vagas-table">
                <tbody>
                    <!-- As próximas vagas serão inseridas aqui via JS -->
                </tbody>
            </table>
        </div>
    </div>';

    return $output;
}