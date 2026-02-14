-- Relatório: Celulares Ativos Sem Usuário Definido
-- Objetivo: Auditoria de qualidade de dados. Lista celulares marcados como 'Em Uso' (active) mas sem nome de usuário vinculado.

SELECT 
    i.id,
    i.asset_code as ID_Ativo,
    i.phone_number as Numero,
    i.brand_model as Modelo,
    i.department as Departamento,
    i.updated_at as Ultima_Alteracao
FROM 
    wp_cellphone_inventory i
WHERE 
    i.deleted = 0 
    AND i.status = 'active'
    AND (i.user_name IS NULL OR TRIM(i.user_name) = '')
ORDER BY 
    i.updated_at DESC;
