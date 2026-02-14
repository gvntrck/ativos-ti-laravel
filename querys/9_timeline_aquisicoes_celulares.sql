-- Relatório: Idade da Frota de Celulares (Timeline de Aquisição)
-- Objetivo: Agrupar a frota pelo ano de cadastro para análise de renovação (Lifecycle).

SELECT 
    YEAR(created_at) as Ano_Aquisicao,
    COUNT(*) as Quantidade_Aparelhos,
    GROUP_CONCAT(DISTINCT brand_model ORDER BY brand_model SEPARATOR ', ') as Modelos_da_Epoca
FROM 
    wp_cellphone_inventory
WHERE 
    deleted = 0
GROUP BY 
    YEAR(created_at)
ORDER BY 
    Ano_Aquisicao DESC;
