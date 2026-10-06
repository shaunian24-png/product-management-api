<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Product_model - reads/writes the `products` table (created by migration 003).
 *
 * Every query uses the query builder / prepared statements.
 */
class Product_model extends Model
{
    protected $table = 'products';

    /**
     * Paginated list with optional search on name/description.
     *
     * @return array [rows, total]
     */
    public function list_products($search, $page, $per_page)
    {
        $where  = '';
        $params = [];

        if ($search !== '') {
            // Escape LIKE wildcards typed by the user
            $like   = '%' . addcslashes($search, '%_\\') . '%';
            $where  = ' WHERE product_name LIKE ? OR description LIKE ?';
            $params = [$like, $like];
        }

        $total = (int) $this->db->raw(
            "SELECT COUNT(*) FROM {$this->table}{$where}",
            $params
        )->fetchColumn();

        $offset = max(0, ($page - 1) * $per_page);

        $rows = $this->db->raw(
            "SELECT id, product_name, description, price, quantity, created_at
               FROM {$this->table}{$where}
              ORDER BY id DESC
              LIMIT " . (int) $per_page . " OFFSET " . (int) $offset,
            $params
        )->fetchAll(PDO::FETCH_ASSOC);

        return [$rows, $total];
    }

    public function find_product($id)
    {
        return $this->db->table($this->table)
            ->where('id', (int) $id)
            ->get() ?: null;
    }

    /** @return int new product id */
    public function create_product(array $data)
    {
        return $this->db->table($this->table)->insert($data);
    }

    /** @return int affected rows */
    public function update_product($id, array $data)
    {
        return $this->db->table($this->table)
            ->where('id', (int) $id)
            ->update($data);
    }

    /** @return int affected rows */
    public function delete_product($id)
    {
        return $this->db->table($this->table)
            ->where('id', (int) $id)
            ->delete();
    }
}
