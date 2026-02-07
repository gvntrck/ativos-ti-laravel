-- RelatÃ³rio 2: DistribuiÃ§Ã£o de Ativos por LocalizaÃ§Ã£o
-- Objetivo: Mostrar a quantidade de equipamentos (Desktop/Notebook) em cada localidade.
-- Ãštil para: GestÃ£o fÃ­sica de patrimÃ´nio e planejamento de infraestrutura.

SELECT 
    -- Usa 'NÃ£o Definido' caso o campo location esteja vazio ou nulo
    COALESCE(NULLIF(location, ''), 'NÃ£o Definido') as Localizacao,
    
    -- Tipo do equipamento (Desktop ou Notebook)
    type as Tipo,
    
    -- Contagem total de itens naquele local e daquele tipo
    COUNT(*) as Quantidade_Total,
    
    -- Contagem apenas dos ativos (exclui backup, manutenÃ§Ã£o, aposentados)
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as Em_Uso,
    
    -- Contagem de itens em backup/estoque naquele local
    SUM(CASE WHEN status = 'backup' THEN 1 ELSE 0 END) as Em_Estoque
FROM 
    wp_computer_inventory
WHERE 
    deleted = 0 -- Exclui itens da lixeira
GROUP BY 
    location, type
ORDER BY 
    Localizacao ASC, Tipo ASC;
