<?

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;


class ProductModel extends Model
{

    public $processid;
    public $product_id;
    public $product_status;
    public $category_type;
    public $amount;
    public $brand;
    public $classification;
    public $name;
    public $size;
    public $subtype;
    public $total_mg_cbd;
    public $total_mg_thc;
    public $uom;
    public $price_sell;
    public $tier_price;
    public $attr_general;
    public $attr_flavors;
    public $attr_effects;
    public $attr_ingredients;
    public $attr_internal_tags;
    public $product_barcodes;
    public $images;
    public $menu_title;
    public $product_description;
    public $dispensaryid;


}

?>