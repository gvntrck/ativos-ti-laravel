-- RelatÃ³rio: Auditoria de Check-in FotogrÃ¡fico (Todos os PCs)
-- Objetivo: Listar todos os computadores com suas fotos mais recentes.
-- OrdenaÃ§Ã£o: Da foto mais recente para a mais antiga (PCs sem foto aparecem no final).

SELECT 
    i.id,
    i.hostname,
    i.notes as Observacoes,
    i.specs as Specs,
    i.type as Tipo,
    COALESCE(NULLIF(i.location, ''), 'Sem Local') as Local_Cadastrado,
    h.created_at as Data_Foto,
    REPLACE(REPLACE(REPLACE(h.photos, '\\/', '/'), '["', ''), '"]', '') as Link_Foto
FROM 
    wp_computer_inventory i
LEFT JOIN 
    wp_computer_history h ON h.computer_id = i.id 
    AND h.created_at = (
        SELECT MAX(h2.created_at) 
        FROM wp_computer_history h2 
        WHERE h2.computer_id = i.id
        AND h2.photos IS NOT NULL AND h2.photos != '' AND h2.photos != 'null' AND h2.photos != '[]'
    )
WHERE 
    i.deleted = 0 
    AND i.status != 'active'
ORDER BY 
    h.created_at DESC;
