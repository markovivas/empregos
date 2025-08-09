# Plugin Vagas Três Corações

Este plugin WordPress importa e exibe automaticamente as vagas de emprego publicadas no site da Prefeitura de Três Corações.

## Funcionalidades

- Busca automática das vagas diretamente do site oficial.
- Atualização automática das vagas apenas 4 vezes ao dia (00:00, 10:00, 18:00, 22:00), reduzindo requisições e otimizando performance.
- Exibição das vagas em uma tabela moderna, responsiva e ordenada alfabeticamente.
- Shortcode fácil de usar para inserir a tabela em qualquer página ou post.

## Instalação

1. Faça upload da pasta do plugin para o diretório `wp-content/plugins` do seu WordPress.
2. Ative o plugin no painel do WordPress.

## Como usar

Adicione o shortcode abaixo em qualquer página ou post onde deseja exibir as vagas:

```
[vagas_tres_coracoes]
```

## Personalização

- O plugin já inclui um CSS moderno e responsivo para a tabela de vagas.
- Para customizar o visual, edite o arquivo `style.css` dentro da pasta do plugin.

## Funcionamento do Cache

- As vagas são atualizadas automaticamente apenas nos horários: 00:00, 10:00, 18:00 e 22:00.
- Entre esses horários, o plugin utiliza cache para evitar múltiplas requisições ao site da Prefeitura.

## Estrutura dos Arquivos

- `tres-coracoes-vagas.php`: Arquivo principal do plugin.
- `includes/functions.php`: Funções de busca, cache e exibição das vagas.
- `style.css`: Estilos da tabela de vagas.

## Observações

- O plugin busca até 15 páginas de vagas por atualização.
- Linhas vazias ou vagas incompletas são automaticamente ignoradas na exibição.
- As vagas são ordenadas alfabeticamente pelo nome da vaga.

## Suporte

Para dúvidas ou sugestões, entre em contato com o desenvolvedor do plugin.