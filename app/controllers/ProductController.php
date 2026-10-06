<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * ProductController - CRUD for the `products` table.
 *
 * Every action calls $this->api->require_jwt() first, so only logged-in
 * users (valid, unexpired access token + existing user row) get through.
 *
 *   GET    /api/products            list (?search=&page=&per_page=)
 *   GET    /api/products/{id}       one product
 *   POST   /api/products            create
 *   PUT    /api/products/{id}       replace (all required fields)
 *   PATCH  /api/products/{id}       partial update
 *   DELETE /api/products/{id}       delete
 */
class ProductController extends Controller
{
    private const MAX_PRICE = 99999999.99;

    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json; charset=utf-8');
        $this->call->library('api');
        $this->call->helper('request');
        $this->call->model('Product_model');
    }

    // GET /api/products
    public function index()
    {
        $this->guard('GET');

        $q        = $this->api->get_query_params();
        $search   = trim((string) ($q['search'] ?? ''));
        $page     = max(1, (int) ($q['page'] ?? 1));
        $per_page = min(100, max(1, (int) ($q['per_page'] ?? 10)));

        // get_query_params() HTML-escapes values; undo it so searching for & works
        $search = htmlspecialchars_decode($search, ENT_QUOTES);

        [$rows, $total] = $this->Product_model->list_products($search, $page, $per_page);

        $this->api->respond([
            'success' => true,
            'data'    => array_map([$this, 'present'], $rows),
            'meta'    => [
                'page'        => $page,
                'per_page'    => $per_page,
                'total'       => $total,
                'total_pages' => max(1, (int) ceil($total / $per_page)),
            ],
        ]);
    }

    // GET /api/products/{id}
    public function show($id)
    {
        $this->guard('GET');

        $this->api->respond([
            'success' => true,
            'data'    => $this->present($this->find_or_404($id)),
        ]);
    }

    // POST /api/products
    public function store()
    {
        $this->guard('POST');

        [$data, $errors] = $this->validate($this->body_or_fail(), false);
        if ($errors) {
            $this->validation_failed($errors);
        }

        $id = $this->Product_model->create_product($data);

        $this->api->respond([
            'success' => true,
            'message' => 'Product created.',
            'data'    => $this->present($this->Product_model->find_product($id)),
        ], 201);
    }

    // PUT /api/products/{id}   and   PATCH /api/products/{id}
    public function update($id)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if (!in_array($method, ['PUT', 'PATCH'], true)) {
            $this->api->respond_error('Method Not Allowed', 405);
        }
        $this->guard($method);
        $this->find_or_404($id);

        // PUT replaces the record (all required fields); PATCH only changes what is sent.
        [$data, $errors] = $this->validate($this->body_or_fail(), $method === 'PATCH');
        if ($errors) {
            $this->validation_failed($errors);
        }
        if (!$data) {
            $this->validation_failed(['body' => 'Send at least one field to update.']);
        }

        $this->Product_model->update_product($id, $data);

        $this->api->respond([
            'success' => true,
            'message' => 'Product updated.',
            'data'    => $this->present($this->Product_model->find_product($id)),
        ]);
    }

    // DELETE /api/products/{id}
    public function destroy($id)
    {
        $this->guard('DELETE');
        $this->find_or_404($id);

        $this->Product_model->delete_product($id);

        $this->api->respond(['success' => true, 'message' => 'Product deleted.']);
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    /** Method check + rate limit + authentication, in that order. */
    private function guard($method)
    {
        $this->api->require_method($method);
        $this->api->rate_limit('products_' . client_ip(), 120, 60);
        return $this->api->require_jwt();
    }

    private function find_or_404($id)
    {
        $product = $this->Product_model->find_product($id);
        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }
        return $product;
    }

    private function body_or_fail()
    {
        $input = json_input();
        if ($input === null) {
            $this->api->respond_error('Request body must be valid JSON.', 400);
        }
        return $input;
    }

    /**
     * Validate and normalise product input.
     *
     * @param array $input
     * @param bool  $partial  true for PATCH: only validate keys that were sent
     * @return array [clean data, errors]
     */
    private function validate(array $input, $partial)
    {
        $data   = [];
        $errors = [];

        // product_name
        if (!$partial || array_key_exists('product_name', $input)) {
            $name = trim((string) ($input['product_name'] ?? ''));
            if ($name === '') {
                $errors['product_name'] = 'Product name is required.';
            } elseif (mb_strlen($name) > 100) {
                $errors['product_name'] = 'Product name must be 100 characters or fewer.';
            } else {
                $data['product_name'] = $name;
            }
        }

        // description (optional)
        if (array_key_exists('description', $input)) {
            $desc = $input['description'];
            if ($desc !== null && !is_scalar($desc)) {
                $errors['description'] = 'Description must be text.';
            } else {
                $desc = trim((string) $desc);
                $data['description'] = $desc === '' ? null : $desc;
            }
        } elseif (!$partial) {
            $data['description'] = null;
        }

        // price
        if (!$partial || array_key_exists('price', $input)) {
            $price = $input['price'] ?? null;
            if ($price === null || $price === '' || !is_numeric($price)) {
                $errors['price'] = 'Price is required and must be a number.';
            } elseif ((float) $price < 0 || (float) $price > self::MAX_PRICE) {
                $errors['price'] = 'Price must be between 0 and ' . self::MAX_PRICE . '.';
            } else {
                $data['price'] = number_format((float) $price, 2, '.', '');
            }
        }

        // quantity
        if (!$partial || array_key_exists('quantity', $input)) {
            $qty = $input['quantity'] ?? null;
            if ($qty === null || $qty === '' || filter_var($qty, FILTER_VALIDATE_INT) === false) {
                $errors['quantity'] = 'Quantity is required and must be a whole number.';
            } elseif ((int) $qty < 0 || (int) $qty > 2147483647) {
                $errors['quantity'] = 'Quantity must be 0 or more.';
            } else {
                $data['quantity'] = (int) $qty;
            }
        }

        return [$data, $errors];
    }

    private function validation_failed(array $errors)
    {
        $this->api->respond([
            'success' => false,
            'error'   => 'Validation failed.',
            'errors'  => $errors,
            'status'  => 422,
        ], 422);
    }

    /** Cast DB strings to proper JSON types and return created_at as UTC ISO-8601. */
    private function present(array $row)
    {
        return [
            'id'           => (int) $row['id'],
            'product_name' => $row['product_name'],
            'description'  => $row['description'],
            'price'        => (float) $row['price'],
            'quantity'     => (int) $row['quantity'],
            'created_at'   => gmdate('Y-m-d\TH:i:s\Z', strtotime($row['created_at'] . ' UTC')),
        ];
    }
}
