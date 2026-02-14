-- Relatório: Inventário de Celulares por Departamento
-- Objetivo: Agrupar celulares ativos por departamento para visualização de alocação.

SELECT 
    IF(department = '' OR department IS NULL, 'Não Definido', department) as Departamento,
    COUNT(*) as Total_Celulares,
    GROUP_CONCAT(DISTINCT user_name ORDER BY user_name SEPARATOR ', ') as Usuarios_Principais
FROM 
    wp_cellphone_inventory
WHERE 
    deleted = 0 AND status = 'active'
GROUP BY 
    department
ORDER BY 
    Total_Celulares DESC;
