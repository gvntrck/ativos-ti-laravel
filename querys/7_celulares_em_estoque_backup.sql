-- Relatório: Celulares em Estoque (Backup) ou Manutenção
-- Objetivo: Listar celulares que não estão em uso ativo e calcular há quanto tempo estão parados/ociosos.

SELECT 
    i.asset_code as ID_Ativo,
    CASE i.status
        WHEN 'backup' THEN 'Backup / Estoque'
        WHEN 'maintenance' THEN 'Em Manutenção'
        ELSE i.status
    END as Situacao,
    i.brand_model as Modelo,
    i.property as Propriedade,
    i.updated_at as Ultima_Movimentacao,
    DATEDIFF(NOW(), i.updated_at) as Dias_Parado
FROM 
    wp_cellphone_inventory i
WHERE 
    i.deleted = 0 
    AND i.status IN ('backup', 'maintenance')
ORDER BY 
    Dias_Parado DESC;
