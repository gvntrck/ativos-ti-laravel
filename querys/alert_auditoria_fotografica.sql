-- Relatório Extra: Auditoria de Check-in Fotográfico (PCs "Perdidos")
-- Objetivo: Identificar computadores que NÃO tiveram "check-in" (foto salva no histórico) nos últimos 5 dias.
-- Contexto: O usuário realiza auditorias tirando fotos; se não há foto recente, o PC pode estar perdido ou inacessível.

SELECT 
    i.id,
    i.hostname,
    i.type as Tipo,
    COALESCE(NULLIF(i.location, ''), 'Sem Local') as Local_Cadastrado,
    
    -- Data da última vez que uma foto foi registrada no histórico deste PC
    MAX(h.created_at) as Data_Ultima_Foto,
    
    -- Quantos dias se passaram desde a última foto (ou NULL se nunca teve)
    DATEDIFF(NOW(), MAX(h.created_at)) as Dias_Sem_Checkin
FROM 
    wp_computer_inventory i
-- Fazemos LEFT JOIN com o histórico filtrando apenas registros que possuem fotos
LEFT JOIN 
    wp_computer_history h ON i.id = h.computer_id 
    AND (h.photos IS NOT NULL AND h.photos != '' AND h.photos != 'null' AND h.photos != '[]')
WHERE 
    i.deleted = 0
    AND i.status != 'retired' -- Ignora computadores já aposentados/baixados
GROUP BY 
    i.id, i.hostname, i.location, i.type
HAVING 
    -- Filtra apenas quem está sem foto há mais de 5 dias OU nunca teve foto
    (Data_Ultima_Foto < DATE_SUB(NOW(), INTERVAL 5 DAY) OR Data_Ultima_Foto IS NULL)
ORDER BY 
    -- Ordena primeiro os que nunca tiveram foto (NULL), depois pelos que estão há mais tempo sem ver
    (Data_Ultima_Foto IS NULL) DESC,
    Dias_Sem_Checkin DESC;
