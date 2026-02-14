-- Relatório: Computadores Ativos Sem Foto
-- Objetivo: Identificar computadores que estão em uso mas não possuem foto cadastrada no sistema.

SELECT 
    hostname as Hostname,
    user_name as Usuario_Responsavel,
    location as Localizacao,
    type as Tipo,
    updated_at as Ultima_Atualizacao
FROM 
    wp_computer_inventory
WHERE 
    deleted = 0 
    AND status = 'active'
    AND (photo_url IS NULL OR photo_url = '')
ORDER BY 
    hostname ASC;
