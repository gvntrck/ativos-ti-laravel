-- Relatório: Status de Auditoria de Celulares
-- Objetivo: Identificar celulares que precisam de auditoria presencial urgente. 
-- Regra: Considera 'Atrasado' se a última auditoria foi há mais de 30 dias.

SELECT 
    i.asset_code as ID_Ativo,
    i.user_name as Usuario_Responsavel,
    i.department as Departamento,
    i.brand_model as Modelo,
    IFNULL(DATE_FORMAT(MAX(h.created_at), '%d/%m/%Y'), '-') as Data_Ultima_Auditoria,
    CASE 
        WHEN MAX(h.created_at) IS NULL THEN 'NUNCA AUDITADO'
        WHEN DATEDIFF(NOW(), MAX(h.created_at)) > 30 THEN CONCAT('ATRASADO (', DATEDIFF(NOW(), MAX(h.created_at)), ' dias)')
        ELSE 'EM DIA'
    END as Status_Auditoria,
    CASE 
        WHEN MAX(h.created_at) IS NULL THEN 1
        WHEN DATEDIFF(NOW(), MAX(h.created_at)) > 30 THEN 2
        ELSE 3
    END as _prioridade_ordenacao
FROM 
    wp_cellphone_inventory i
LEFT JOIN 
    wp_cellphone_history h ON i.id = h.cellphone_id AND h.event_type = 'audit'
WHERE 
    i.deleted = 0 
    AND i.status = 'active'
GROUP BY 
    i.id
ORDER BY 
    _prioridade_ordenacao ASC, -- Prioridade: Nunca Auditado -> Atrasado -> Em Dia
    MAX(h.created_at) ASC;     -- Dentro dos atrasados, os mais antigos primeiro
