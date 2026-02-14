-- Relatório: Lista de Celulares com Última Auditoria
-- Objetivo: Listar todos os celulares ativos com seus dados principais e a data da última auditoria presencial.
-- Ordenação: Da atualização mais recente para a mais antiga.

SELECT 
    i.id,
    i.asset_code,
    i.phone_number,
    i.status,
    i.deleted,
    i.user_name,
    i.brand_model,
    i.department,
    i.property,
    i.notes,
    i.photo_url,
    i.created_at,
    i.updated_at,
    audit.last_audit_at
FROM 
    wp_cellphone_inventory i
LEFT JOIN (
    -- Subquery para encontrar a data da última auditoria de cada celular
    SELECT 
        cellphone_id, 
        MAX(created_at) as last_audit_at
    FROM 
        wp_cellphone_history
    WHERE 
        event_type = 'audit'
    GROUP BY 
        cellphone_id
) audit ON i.id = audit.cellphone_id
WHERE 
    i.deleted = 0
ORDER BY 
    i.updated_at DESC;
