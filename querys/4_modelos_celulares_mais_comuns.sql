-- Relatório: Ranking de Modelos de Celulares
-- Objetivo: Identificar quais modelos de aparelhos são mais comuns na frota ativa.

SELECT 
    IF(brand_model = '' OR brand_model IS NULL, 'Modelo Não Informado', brand_model) as Modelo,
    COUNT(*) as Quantidade
FROM 
    wp_cellphone_inventory
WHERE 
    deleted = 0 
    AND status = 'active'
GROUP BY 
    brand_model
ORDER BY 
    Quantidade DESC
LIMIT 20;
