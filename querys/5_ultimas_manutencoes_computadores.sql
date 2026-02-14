-- Relatório: Últimas 50 Manutenções em Computadores
-- Objetivo: Listar os últimos registros de manutenção técnica e atualizações de Windows.

SELECT 
    i.hostname as Computador,
    i.user_name as Usuario_Atual,
    h.created_at as Data_Manutencao,
    h.description as Descricao_Servico,
    (SELECT display_name FROM wp_users WHERE ID = h.user_id) as Tecnico_Responsavel
FROM 
    wp_computer_history h
JOIN 
    wp_computer_inventory i ON h.computer_id = i.id
WHERE 
    h.event_type IN ('maintenance', 'quick_windows_update')
    AND i.deleted = 0
ORDER BY 
    h.created_at DESC
LIMIT 50;
