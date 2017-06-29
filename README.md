bea-content-sync-fusion
=======================

Manage content synchronisation across a WordPress multisite

### 3.0.0
 * 29 June 2017
 * Work only on relations table, do not use old meta _origin_key
 * Synchronisations are bidirectional
 * Remove old code from notifications

### 2.0.2
 * Add filter bea_csf.client.post_type.allow_bidirectional_sync to allow bidirectional synchronisation

### 2.0.1
 * Fix P2P synchronisation

### 2.0.0
 * Remove media synchronisation using symlink. Use shared uploads folder.
 * Remove old code for old term meta API.
 * Use term_id instead tt_id.

### 1.1
 * Stable version using WordPress metadata API for Taxonomy.