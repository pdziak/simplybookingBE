-- Clean up empty EventPerson records
-- This script removes EventPerson records with empty or null person_fullname

-- First, let's see what we have
SELECT 
    ep.id,
    ep.event_id,
    ep.person_fullname,
    e.title as event_title
FROM event_persons ep
LEFT JOIN events e ON ep.event_id = e.id
WHERE ep.person_fullname IS NULL 
   OR ep.person_fullname = '' 
   OR TRIM(ep.person_fullname) = '';

-- Delete empty EventPerson records
DELETE FROM event_persons 
WHERE person_fullname IS NULL 
   OR person_fullname = '' 
   OR TRIM(person_fullname) = '';

-- Verify the cleanup
SELECT COUNT(*) as remaining_empty_records
FROM event_persons 
WHERE person_fullname IS NULL 
   OR person_fullname = '' 
   OR TRIM(person_fullname) = '';
