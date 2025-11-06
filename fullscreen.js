jQuery(document).ready(function ($) {
    const vagas = vagasData.vagas || [];
    let timerInterval;
    let remainingVagas = [];
    let historyVagas = [];
    let scrollInterval;

    const intervalTime = 5000; // 5 segundos

    const container = $('#tc-fullscreen-container');
    const mainContent = $('#tc-fullscreen-main');
    const sidebar = $('#tc-fullscreen-sidebar');
    const vagaTitle = $('#tc-vaga-title');
    const vagaDetails = $('#tc-vaga-details');
    const progressBar = $('#tc-progress-bar');
    const nextVagasTable = $('#tc-next-vagas-table tbody');
    const sortedVagas = [...vagas].sort((a, b) => a.vaga.localeCompare(b.vaga));

    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }

    function stopSidebarScroll() {
        clearInterval(scrollInterval);
    }

    function populateAndScrollSidebar() {
        stopSidebarScroll();
        nextVagasTable.empty();

        if (sortedVagas.length === 0) {
            nextVagasTable.append('<tr><td>Nenhuma vaga disponível.</td></tr>');
            return;
        }

        sortedVagas.forEach(vaga => {
            nextVagasTable.append(`<tr><td>${vaga.vaga}</td></tr>`);
        });

        // Inicia a rolagem automática
        scrollInterval = setInterval(() => {
            const currentScrollTop = sidebar.scrollTop();
            const scrollHeight = sidebar.prop('scrollHeight');
            const clientHeight = sidebar.prop('clientHeight');
            if (currentScrollTop + clientHeight >= scrollHeight - 1) {
                sidebar.scrollTop(0); // Volta ao topo
            } else {
                sidebar.scrollTop(currentScrollTop + 1); // Velocidade da rolagem
            }
        }, 50); // Intervalo da rolagem (em ms)
    }

    function showRandomVaga() {
        // Se a lista de vagas restantes estiver vazia, reabasteça-a
        if (remainingVagas.length === 0) {
            if (historyVagas.length === 0) { // Primeira execução
                remainingVagas = shuffleArray([...vagas]);
            } else { // Ciclo completado, recomeça
                remainingVagas = shuffleArray([...historyVagas]);
                historyVagas = [];
            }
        }

        // Pega a próxima vaga da lista, move para o histórico
        const vaga = remainingVagas.shift();
        if (!vaga) {
            stopFullscreen();
            return;
        }
        historyVagas.push(vaga);

        const contentElement = $('#tc-fullscreen-content');

        // 1. Remove a classe para que a animação possa ser reativada
        contentElement.removeClass('fade-in-active');

        // 2. Aguarda um instante, atualiza o conteúdo e readiciona a classe de animação
        setTimeout(() => {
            vagaTitle.text(vaga.vaga);
            vagaDetails.text(''); // Remove cidade e quantidade
            contentElement.addClass('fade-in-active');
        }, 50); // Um pequeno delay para garantir que o navegador processe a remoção da classe

        mainContent.css('background-color', '#FFC600');
        progressBar.stop().css('width', '0%').animate({ width: '100%' }, intervalTime, 'linear');
    }

    function startFullscreen() {
        if (vagas.length === 0) {
            alert('Não há vagas para exibir em tela cheia.');
            return;
        }

        // Reseta o estado
        remainingVagas = [];
        historyVagas = [];

        container.show();
        document.documentElement.requestFullscreen().catch(err => {
            console.error(`Erro ao tentar ativar o modo tela cheia: ${err.message} (${err.name})`);
        });

        populateAndScrollSidebar();
        showRandomVaga();
        timerInterval = setInterval(showRandomVaga, intervalTime);
    }

    function stopFullscreen() {
        clearInterval(timerInterval);
        stopSidebarScroll();
        container.hide();
        if (document.fullscreenElement) {
            document.exitFullscreen();
        }
    }

    // Event Listeners
    $('#tc-activate-fullscreen').on('click', startFullscreen);
    $('#tc-exit-fullscreen').on('click', stopFullscreen);

    $(document).on('fullscreenchange', function () {
        if (!document.fullscreenElement) {
            stopFullscreen();
        }
    });

    // Parar se a tecla ESC for pressionada
    $(document).on('keydown', function(e) {
        if (e.key === "Escape" && document.fullscreenElement) {
            stopFullscreen();
        }
    });
});