/*
*  Find lowest common ancestor in BST
*/

class Node
{
    private $left = null;
    private $right = null;
    private $value = 0;

    function __construct($val)
    {
        $this->value = $val;
    }

    public function getLeft() { return $this->left; }
    public function setLeft($node) { $this->left = $node; }

    public function getRight() { return $this->right; }
    public function setRight($node) { $this->right = $node; }

    public function getValue() { return $this->value; }
    public function setValue($val) { $this->value = $val; }
}


function findLowestCommonAncestor(Node $root, $value1, $value2)
{
    while ($root != null)
    {
        $value = $root->getValue();

        if ($value > $value1 && $value > $value2)
        {
            $root = $root->getLeft();
        }
        else if ($value < $value1 && $value < $value2)
        {
            $root = $root->getRight();
        }
        else
        {
            return $root;
        }
    }
    return null; // only if empty tree
}