# How to Rename "Certificate of Good Moral" to "Community Tax"

## Method 1: Using phpMyAdmin (Recommended)

1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Select your database (likely `bm_scapis` or similar)
3. Click on the "SQL" tab
4. Copy and paste the SQL from `migrations/rename_certificate_to_community_tax.sql`
5. Click "Go" to execute

## Method 2: Using MySQL Command Line

```bash
mysql -u root -p bm_scapis < migrations/rename_certificate_to_community_tax.sql
```

## Method 3: Run SQL Directly

Execute this SQL query in your MySQL database:

```sql
UPDATE document_types 
SET type_name = 'Community Tax (Cedula)',
    description = 'Community Tax Certificate (also known as Cedula)'
WHERE type_name = 'Certificate of Good Moral';
```

## What This Does

- Changes the document type name from "Certificate of Good Moral" to "Community Tax (Cedula)"
- Updates the description to be more accurate
- All existing applications with this document type will automatically show the new name
- No changes needed to PHP files - they read from the database

## Verification

After running the migration:

1. Go to the Document Types page in the admin panel
2. You should see "Community Tax (Cedula)" instead of "Certificate of Good Moral"
3. Check existing applications - they will now show the new name
4. New applications will use the updated name

## Rollback (if needed)

If you need to revert the change:

```sql
UPDATE document_types 
SET type_name = 'Certificate of Good Moral',
    description = 'Character certificate'
WHERE type_name = 'Community Tax (Cedula)';
```
