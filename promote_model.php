<?php

class Promote_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    //$userID will promote $productID and requests $returnProductID to be reviewed in return
    function insert_user_promote($userID, $productID, $returnRequest, $returnProductID)
    {
        $data = array(
            'userID' => $userID,
            'productID' => $productID,
            'returnRequest' => $returnRequest,
            'returnProductID' => $returnProductID,
            'created' => date('Y-m-d H:i:s')
        );

        $this->db->insert('user_promote', $data);
        return $this->db->insert_id();
    }

    function get_user_promote($userPromoteID)
    {
        $query = $this->db->get_where('user_promote', array('userPromoteID' => $userPromoteID));
        return $query->row_array();
    }

    //get all the product promotions $userID has said they would do/have done
    function get_user_promotes($userID, $confirmed)
    {
        $query = $this->db->get_where('user_promote', array('userID' => $userID, 'confirmed' => $confirmed));
        return $query->result_array();
    }

    function get_user_promotes_full($userID, $confirmed)
    {
        $sql = 'SELECT up.userID, up.productID, up.returnRequest, up.returnProductID, up.done, p.name, p.slug FROM user_promote up ';
        $sql .= 'INNER JOIN product p ON up.productID=p.productID ';
        $sql .= 'WHERE up.userID=? AND up.confirmed=? ';
        $sql .= 'ORDER BY up.created DESC';
        $query = $this->db->query($sql, array($userID, $confirmed));
        $promotes = $query->result_array();
        $data = array();
        foreach ($promotes as $p)
        {
            $p['viewURL'] = site_url('products/view') . '/' . $p['slug'];
            $p['ownerLinkHTML'] = $this->Product_model->get_product_owner_profile_link($p['productID']);
            $data[] = $p;
        }

        return $data;
    }

    //get all the users promoting (or have promoted) $userIDs products
    function get_promoting($userID, $confirmed)
    {
        $sql = 'SELECT up.userID, up.productID, up.done, p.name, p.slug FROM user_promote up ';
        $sql .= 'INNER JOIN product p ON up.productID=p.productID AND p.userID=? ';
        $sql .= ' WHERE up.confirmed=?';

        $query = $this->db->query($sql, array($userID, $confirmed));
        return $query->result_array();
    }

    function get_promoting_full($userID, $confirmed)
    {
        $sql = 'SELECT up.userPromoteID, up.userID, up.productID, up.done, p.name, p.slug, u.username FROM user_promote up ';
        $sql .= 'INNER JOIN product p ON up.productID=p.productID AND p.userID=? ';
        $sql .= 'INNER JOIN user u ON up.userID=u.userID';
        $sql .= ' WHERE up.confirmed=?';

        $query = $this->db->query($sql, array($userID, $confirmed));
        $promoting = $query->result_array();
        $data = array();
        foreach ($promoting as $p)
        {
            $p['userURL'] = site_url('profile/view') . '/' . $p['username'];
            $p['productURL'] = site_url('products/view') . '/' . $p['slug'];
            $data[] = $p;
        }

        return $data;
    }

    //work out how many promotes are owed to $userID. Negative means $userID owes promotes
    function get_number_promotes_owed($userID)
    {
        $promotes = $this->get_user_promotes($userID, 1);
        $promoted = $this->get_promoting($userID, 1);

        return count($promotes) - count($promoted);
    }

    //TODO: get list of users who owe reciprocal promotions to this user
    function get_who_owes_user_promotes($userID)
    {

    }

    //how many times has productID been reviewed?
    function get_number_of_reviews($productID)
    {
        $query = $this->db->get_where('user_promote', array('confirmed' => 1, 'productID' => $productID));
        return $query->num_rows();
    }

    //is $userID promoting $productID?
    function is_promoting($userID, $productID)
    {
        $query = $this->db->get_where('user_promote', array('userID' => $userID, 'confirmed' => 0, 'productID' => $productID));
        if ($query->num_rows() === 0)
            return false;
        return true;
    }

    function has_promoted($userID, $productID)
    {
        $query = $this->db->get_where('user_promote', array('userID' => $userID, 'confirmed' => 1, 'productID' => $productID));
        if ($query->num_rows() === 0)
            return false;
        return true;
    }

    function has_promoted_awaiting_confirmation($userID, $productID)
    {
        $query = $this->db->get_where('user_promote', array('userID' => $userID, 'done' => 1, 'confirmed' => 0, 'productID' => $productID));
        if ($query->num_rows() === 0)
            return false;
        return true;
    }

    //$userID has finished promoting $productID
    function complete_promotion($userID, $productID, $details)
    {
        $data = array(
            'done' => 1,
            'doneDate' => date('Y-m-d H:i:s'),
            'doneDetails' => $details
        );

        $this->db->where('userID', $userID);
        $this->db->where('productID', $productID);
        $this->db->update('user_promote', $data);
    }

    //product owner is confirming that the promotion is complete
    function confirm_promotion($userPromoteID)
    {
        $this->db->where('userPromoteID', $userPromoteID);
        $this->db->update('user_promote', array('confirmed' => 1));
    }

    //need to check if $userID is promoting $productID as a reciprocation for another user promoting their product
    public function is_reciprocal_promotion($userID, $productID)
    {
        $sql = 'SELECT up.userPromoteID FROM user_promote up ';
        $sql .= 'INNER JOIN product p ON up.productID=p.productID AND p.userID=? ';  //check if this user is the owner of a product reviewed by another with a return request
        $sql .= 'WHERE up.returnRequest=1 AND returnProductID=? '; //i.e. is this product being requested for a return review in any rows?
        $query = $this->db->query($sql, array($userID,$productID));
        if ($query->num_rows() > 0) return TRUE;

        return FALSE;
    }

}