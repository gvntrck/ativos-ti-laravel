-- Relatório: Distribuição de Computadores por Status
-- Objetivo: Mostrar a quantidade e percentual de computadores em cada status (Ativo, Backup, Manutenção, Aposentado).

SELECT 
    CASE status
        WHEN 'active' THEN 'Em Uso'
        WHEN 'backup' THEN 'Backup'
        WHEN 'maintenance' THEN 'Em Manutenção'
        WHEN 'retired' THEN 'Aposentado'
        ELSE status
    END as Status_Formatado,
    COUNT(*) as Quantidade,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM wp_computer_inventory WHERE deleted = 0), 1), '%') as Porcentagem
FROM 
    wp_computer_inventory
WHERE 
    deleted = 0
GROUP BY 
    status
ORDER BY 
    Quantidade DESC;
