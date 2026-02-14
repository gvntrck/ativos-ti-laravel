-- Relatório: Inventário Completo de Celulares para Conferência
-- Objetivo: Listagem detalhada "wide" com todos os dados principais e data da última auditoria presencial.

SELECT 
    i.asset_code as Patrimonio,
    i.phone_number as Numero,
    i.brand_model as Modelo,
    i.user_name as Usuario,
    i.department as Departamento,
    i.property as Empresa_Proprietaria,
    CASE i.status
        WHEN 'active' THEN 'Ativo'
        WHEN 'backup' THEN 'Backup'
        WHEN 'maintenance' THEN 'Manutenção'
        WHEN 'retired' THEN 'Aposentado'
        ELSE i.status
    END as Status,
    DATE_FORMAT(i.created_at, '%d/%m/%Y') as Data_Cadastro,
    IFNULL(DATE_FORMAT(MAX(h.created_at), '%d/%m/%Y %H:%i'), '-') as Ultima_Auditoria_Presencial
FROM 
    wp_cellphone_inventory i
LEFT JOIN 
    wp_cellphone_history h ON i.id = h.cellphone_id AND h.event_type = 'audit'
WHERE 
    i.deleted = 0
GROUP BY 
    i.id
ORDER BY 
    i.asset_code ASC;
