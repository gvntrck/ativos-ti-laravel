-- Relatório: Ranking de Manutenção em Celulares
-- Objetivo: Identificar aparelhos problemáticos com base no histórico de intervenções (excluindo auditorias e cadastros).

SELECT 
    i.asset_code as ID_Ativo,
    i.brand_model as Modelo,
    i.phone_number as Numero,
    i.user_name as Usuario_Atual,
    COUNT(h.id) as Total_Intervencoes,
    MAX(h.created_at) as Ultima_Intervencao
FROM 
    wp_cellphone_inventory i
JOIN 
    wp_cellphone_history h ON i.id = h.cellphone_id
WHERE 
    i.deleted = 0
    AND h.event_type NOT IN ('audit', 'create', 'checkup', 'update')
GROUP BY 
    i.id
ORDER BY 
    Total_Intervencoes DESC
LIMIT 50;
