<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Products extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Product_model');
        $this->load->model('User_model');
        $this->load->library('pagination');
    }

    public function index()
    {
        //setup pagination
        $config['base_url'] = base_url() . 'products/index';
        $config['total_rows'] = $this->Product_model->count_all_products();
        $config['per_page'] = '10';
        $config['full_tag_open'] = '<ul class="pagination">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['next_link'] = '&raquo;';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_link'] = '&laquo;';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $this->pagination->initialize($config);

        $data['results'] = $this->Product_model->get_products_pagination($config['per_page'], $this->uri->segment(3));

        $this->load->view('products_page', $data);
    }

    function view($slug = null)
    {
        if ($slug)
        {
            $product = $this->Product_model->get_product_by_slug($slug);
            if (count($product) > 0)
            {
                if ($product['active'] == 1)
                {
                    $this->load->view('view_product_page', $product);
                }
                else
                {
                    $data['name'] = $product['name'];
                    $this->load->view('view_deleted_product_page', $data);
                }

            }
            else redirect('products');
        }
        else redirect('products');
    }

    function edit($slug = null)
    {
        if (!$this->session->userdata('LoggedIn')) redirect('products');

        if ($slug)
        {
            $product = $this->Product_model->get_product_by_slug($slug);
            if (count($product) > 0)
            {
                $userID = $this->session->userdata('userID');
                if ($product['userID'] == $userID)
                {
                    $this->load->view('edit_product_page', $product);
                }
                else redirect('products');
            }
            else redirect('products');
        }
        else redirect('products');
    }

    function edit_image($slug = null)
    {
        if (!$this->session->userdata('LoggedIn')) redirect('products');

        if ($slug)
        {
            $product = $this->Product_model->get_product_by_slug($slug);
            if (count($product) > 0)
            {
                $userID = $this->session->userdata('userID');
                if ($product['userID'] == $userID)
                {
                    $product['errors'] = '';
                    $this->load->view('edit_product_image_page', $product);
                }
                else redirect('products');
            }
            else redirect('products');
        }
        else redirect('products');
    }

    function remove_image($productID = null)
    {
        if (!$this->session->userdata('LoggedIn')) redirect('products');

        if ($productID)
        {
            $product = $this->Product_model->get_product($productID);
            if (count($product) > 0)
            {
                $userID = $this->session->userdata('userID');
                if ($product['userID'] == $userID)
                {
                    $noImgURL = site_url('assets/img/no_image.gif');

                    if (strcmp($product['img'], $noImgURL) != 0)
                    {
                        $this->_delete_from_s3($product['img']);
                        $this->Product_model->update_product_image($productID, $noImgURL);
                    }
                    $this->load->view('view_product_page', $product);
                }
                else redirect('products');
            }
            else redirect('products');
        }
        else redirect('products');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////

    function get_product_json($productID = null)
    {
        if ($productID)
        {
            echo json_encode($this->Product_model->get_product($productID));
        }
    }

    function update_product()
    {
        if ($this->session->userdata('LoggedIn'))
        {
            $userID = $this->session->userdata('userID');
            $productID = $this->input->post('productID');
            if ($this->Product_model->get_owner($productID) == $userID)
            {
                $name = $this->Util_model->validate_varchar($this->input->post('name', TRUE));
                $types = $this->Util_model->validate_varchar($this->input->post('types', TRUE));
                $desc = $this->Util_model->validate_text($this->input->post('description', TRUE));
                $instructions = $this->Util_model->validate_text($this->input->post('instructions', TRUE));
                $url = $this->Util_model->validate_varchar($this->input->post('url', TRUE));

                $this->Product_model->update_product($productID, $name, $desc, $url, $types, $instructions);
            }
        }
    }

    function update_product_image()
    {
        if ($this->session->userdata('LoggedIn'))
        {
            $userID = $this->session->userdata('userID');
            $productID = $this->input->post('productID');
            $slug = $this->input->post('slug');
            if ($this->Product_model->get_owner($productID) == $userID)
            {
                $config['upload_path'] = './uploads/';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '10240';
                $config['max_width'] = '1024';
                $config['max_height'] = '768';

                $this->load->library('upload', $config);

                if (!$this->upload->do_upload())
                {
                    $product = $this->Product_model->get_product_by_slug($slug);
                    $product['errors'] = $this->upload->display_errors();
                    $this->load->view('edit_product_image_page', $product);
                }
                else
                {
                    //upload the file to S3 and delete original from server
                    $image = $this->upload->data();

                    $this->load->library('s3');
                    $this->s3->setAuth($this->config->item('awsAccessKey'), $this->config->item('awsSecretKey'));
                    //$this->s3->setSSL(true);
                    $s3FileName = "products/" . $productID . "/image1" . $image['file_ext'];

                    try
                    {
                        $result = $this->s3->putObjectFile($image['full_path'], "wecombinate", $s3FileName, S3::ACL_PUBLIC_READ);

                        if ($result)
                        {
                            $fullAWSPath = "https://s3.amazonaws.com/wecombinate/" . $s3FileName;
                            $this->Product_model->update_product_image($productID, $fullAWSPath);

                            //TODO: delete original??  could just setup cron job to do it...

                            //TODO: remove previous image(s) from S3 before uploading new one to save space & money!  NOTE: same name image will overwrite on S3
                            //TODO: so most you could ever have is 3 x 10MB images: image1.jpg, image1.png, image1.gif
                        }
                        else
                        {
                            $this->session->set_flashdata('message', 'There was a problem uploading the file. Please try again later');
                            log_message('error', 'S3 putObjectFile FAILED');
                        }
                    }
                    catch (S3Exception $e)
                    {
                        $this->session->set_flashdata('message', 'There was a problem uploading the file. Please try again later');
                        log_message('error', 'S3 Exception: ' . $e->getMessage());
                    }

                    redirect('products/view/' . $slug);
                }

            }
        }
    }

    function remove_product()
    {
        if ($this->session->userdata('LoggedIn'))
        {
            $userID = $this->session->userdata('LoggedIn');
            $productID = $this->input->post('productID');
            if ($this->Product_model->get_owner($productID) == $userID)
            {
                $this->Product_model->set_inactive($productID);
                //$this->Product_model->delete_product($productID);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    function _delete_from_s3($image)
    {
        $splitStr = "https://s3.amazonaws.com/wecombinate/";
        $imageURI = explode($splitStr, $image);

        try
        {
            $this->load->library('s3');
            $this->s3->setAuth($this->config->item('awsAccessKey'), $this->config->item('awsSecretKey'));
            $this->s3->deleteObject('wecombinate', $imageURI[1]);
        }
        catch (S3Exception $e)
        {
            log_message('error', 'S3 Exception on delete: ' . $e->getMessage());
        }
    }
}
