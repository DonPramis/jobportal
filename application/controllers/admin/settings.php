<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/settings_model');
        $this->load->library('form_validation');
        $this->load->helper('file');
        $this->helper_model->validate_session();
    }

    public function index() {
        $this->site_settings();
    }


    public function site_settings() {
        $this->form_validation->set_rules('site_name', 'Site Name', 'required|xss_clean|min_length[2]|max_length[32]');
        $this->form_validation->set_rules('site_title', 'Site Title', 'xss_clean');
        $this->form_validation->set_rules('site_slogan', 'Site Title', 'xss_clean');
        $this->form_validation->set_rules('site_email', 'Site Title', 'required|xss_clean');
        $this->form_validation->set_rules('facebook', 'Facebook Link', 'xss_clean');
        $this->form_validation->set_rules('twitter', 'Twitter Link', 'xss_clean');
        $this->form_validation->set_rules('youtube', 'Youtube', 'xss_clean');
        $this->form_validation->set_rules('meta_description', 'Meta Description', 'xss_clean');
        $this->form_validation->set_rules('meta_keywords', 'Meta Keywords', 'xss_clean');
        $this->form_validation->set_rules('site_authors', 'Site Authors', 'xss_clean');
        $this->form_validation->set_rules('site_status', 'Site Status', 'xss_clean');
        $this->form_validation->set_rules('prev_image', 'Previous Image', 'xss_clean');
        $this->form_validation->set_rules('site_offline_msg', 'Site Offline Message', 'required|xss_clean');

        $this->helper_model->editor();
        
        if ($this->form_validation->run() == FALSE) {        
            $data['info'] = $this->settings_model->get_site_settings();
            $data['main'] = 'admin/site_settings';
            $data['title'] = 'Site Settings';

            $this->load->view('admin/admin', $data);
        } else {
            $uploaded_details = $this->upload_image('logo');
            if ($uploaded_details['file_name'] != '') {
                $image = $uploaded_details['file_name'];
                //echo ("./uploads/admin/images/" . $this->input->post('prev_image')); exit;
                if (file_exists("./uploads/admin/images/" . $this->input->post('prev_image'))){
                    //echo (base_url()."uploads/admin/images/" . $this->input->post('prev_image')); exit;
                    @unlink("./uploads/admin/images/" . $this->input->post('prev_image'));
                }
            } else {
                $image = $this->input->post('prev_image');
            }

            if($this->settings_model->update_site_settings($image)) {
                $this->session->set_userdata( 'flash_msg_type', "success" );
                $this->session->set_flashdata('flash_msg', 'Site Settings Updated Successfully');
                redirect(ADMIN_PATH . '/settings/site_settings', 'refresh');
            } else {
                $this->session->set_userdata( 'flash_msg_type', "danger" );
                $this->session->set_flashdata('flash_msg', 'Sorry, Unable to Update Settings');
                redirect(ADMIN_PATH . '/settings/site_settings', 'refresh');
            }
        }
    }


    function upload_image($file) {
        $config['upload_path'] = './uploads/admin/images/';
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = '10000000';
        $config['max_width'] = '65';
        $config['max_height'] = '50';
        $config['remove_spaces'] = true;
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);
        //var_dump($file); exit;
        $this->upload->do_upload($file);
        if ($this->upload->display_errors()) {
            $this->error = $this->upload->display_errors();
            //echo $this->error; exit;
            return true;
        } else {
            $data = $this->upload->data();
            return $data;
        }
    }


    public function change_password() {
        $this->form_validation->set_rules('cPassword', 'Current Password', 'required|xss_clean|callback_verify_current_pass');
        $this->form_validation->set_rules('password', 'Password', 'required|xss_clean|min_length[6]|max_length[64]');
        $this->form_validation->set_rules('confirmPassword', 'Confirm Password', 'required|xss_clean|matches[password]');

        if ($this->form_validation->run() == FALSE) {
            $data['main'] = 'admin/change_password';
            $data['title'] = 'Change Password';
            $this->load->view('admin/admin', $data);
        } else {
            $password = $this->helper_model->encrypt_me($this->input->post('password'));
            if($this->settings_model->update_password($password)) {
                $this->session->set_userdata( 'flash_msg_type', "success" );
                $this->session->set_flashdata('flash_msg', 'Password Changed Successfully');
                redirect(ADMIN_PATH . '/settings/change_password', 'refresh');
            } else {
                $this->session->set_userdata( 'flash_msg_type', "danger" );
                $this->session->set_flashdata('flash_msg', 'Sorry, Unable to Change the Password');
                redirect(ADMIN_PATH . '/settings/change_password', 'refresh');
            }
        }
    }


    public function verify_current_pass() {
        if($this->settings_model->verify_current_pw()) {
            return true;
        } else {
            $this->form_validation->set_message('verify_current_pass','Current Password Incorrect');
            return false;
        }
    }


    public function email_templates($template_code='REGISTRATION'){
        $this->form_validation->set_rules('subject', 'Subject', 'required|xss_clean');
        $this->form_validation->set_rules('content', 'Email Body', 'required|xss_clean');

        $this->helper_model->editor();

         if ($this->form_validation->run() == FALSE) {
            $data['info'] = $this->settings_model->get_email_template($template_code);
            $data['main'] = 'admin/email_templates';
            $data['title'] = 'Email Templates';
            $this->load->view('admin/admin', $data);
        } else {
            if($this->settings_model->update_email_template()) {
                $this->session->set_userdata( 'flash_msg_type', "success" );
                $this->session->set_flashdata('flash_msg', 'Email Template Updated Successfully');
                redirect(ADMIN_PATH . '/settings/email_templates/' . $this->input->post('temp_name'), 'refresh');
            } else {
                $this->session->set_userdata( 'flash_msg_type', "danger" );
                $this->session->set_flashdata('flash_msg', 'Sorry, Unable to Update Email Template');
                redirect(ADMIN_PATH . '/settings/email_templates' . $this->input->post('temp_name'), 'refresh');
            }

        }

    }


    public function cms($title='about_us') {
        $this->form_validation->set_rules('head_text', 'Heading', 'required|xss_clean');
        $this->form_validation->set_rules('page_title', 'Page Title', 'required|xss_clean');
        $this->form_validation->set_rules('content', 'Content', 'required|xss_clean');
        $this->form_validation->set_rules('meta_keywords', 'Meta Keywords', 'required|xss_clean');
        $this->form_validation->set_rules('status', 'Status', 'required|xss_clean');        
        $this->form_validation->set_rules('meta_description', 'Meta Description', 'required|xss_clean');

        $this->helper_model->editor();

        $page_title=$this->input->post('page_title');

         if($this->form_validation->run() == FALSE) {
            $data['info'] = $this->settings_model->get_cms($title);
            //echo json_encode($data['info']); exit;
            $data['select_info'] = $this->settings_model->get_cms();
            //echo json_encode($data['select_info']); exit;
            $data['main'] = 'admin/cms';
            $data['title'] = 'Content Management';
            if(!(validation_errors())) {
                $this->load->view('admin/admin', $data);
            } else {
                /*$data['main'] = $this->input->post('cms_page');
                $this->load->view('admin/admin', $data);*/
                //redirect(ADMIN_PATH . '/settings/cms/' . $this->input->post('cms_page'));
                //$this->cms($this->input->post('cms_page'));
                redirect(ADMIN_PATH . '/settings/cms/' . $this->input->post('cms_page'), 'refresh');
            }
        } else {
            if($this->settings_model->update_cms($this->input->post('cms_page'))) {
                $this->session->set_userdata( 'flash_msg_type', "success" );
                $this->session->set_flashdata('flash_msg', $this->input->post('page_title').' Page Updated Successfully');
                redirect(ADMIN_PATH . '/settings/cms/' . $this->input->post('cms_page'), 'refresh');
            } else {
                $this->session->set_userdata( 'flash_msg_type', "danger" );
                $this->session->set_flashdata('flash_msg', 'Sorry, Unable to Update the Page Currently.');
                redirect(ADMIN_PATH . '/settings/cms/' . $this->input->post('cms_page'), 'refresh');
            }

        }

    }


    public function contact_details(){
        $this->form_validation->set_rules('phone', 'Phone', 'required|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'valid_email|required|xss_clean');
        $this->form_validation->set_rules('fax', 'Fax', 'trim|xss_clean');
        $this->form_validation->set_rules('weekday_start_time', 'Time', 'required|trim|xss_clean|callback_validate_time');
        $this->form_validation->set_rules('weekday_end_time', 'Time', 'required|trim|xss_clean|callback_validate_time');
        $this->form_validation->set_rules('weekend_start_time', 'Time', 'required|trim|xss_clean|callback_validate_time');
        $this->form_validation->set_rules('weekend_end_time', 'Time', 'required|trim|xss_clean|callback_validate_time');
        $this->form_validation->set_rules('lat', 'Latitude', 'required|xss_clean');
        $this->form_validation->set_rules('lon', 'Longitude', 'required|xss_clean');

        if ($this->form_validation->run() == FALSE) {
            $data['info'] = $this->settings_model->get_contact_info();
            $data['main'] = 'admin/contact_details';

            $data['title'] = 'Contact Details';
            $this->load->view('admin/admin', $data);
        } else {
            if($this->settings_model->update_contact()) {
                $this->session->set_userdata( 'flash_msg_type', "success" );
                $this->session->set_flashdata('flash_msg', 'Contact Details Updated Successfully');
                redirect(ADMIN_PATH . '/settings/contact_details','refresh');
            } else {
                $this->session->set_userdata( 'flash_msg_type', "danger" );
                $this->session->set_flashdata('flash_msg', 'Sorry, Unable to Update Contact Details');
                redirect(ADMIN_PATH . '/settings/contact_details', 'refresh');
            }

        }

    }

    public function validate_time($str) {
        //Assume $str SHOULD be entered as HH:MM
        $time = explode(':', $str);
        if(isset($time[0]) && isset($time[1]) && (count($time)==2)) {
            if (!is_numeric($time[0]) || !is_numeric($time[1])) {
                return FALSE;
            } else if ((int) $time[0] > 23 || (int) $time[1] > 59) {
                return FALSE;
            } else if ((int) $time[0] < 0 || (int) $time[1] < 0) {
                return false;
            } else if (mktime((int) $time[0], (int) $time[1]) === FALSE) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            return FALSE;
        }
    }


    public function email_settings() {
        $this->form_validation->set_rules('mailtype', 'Mail Type', 'required|xss_clean');
        $this->form_validation->set_rules('protocol', 'Protocol', 'required|xss_clean');
        $this->form_validation->set_rules('smtp_host', 'SMTP Host', 'required');
        $this->form_validation->set_rules('smtp_port', 'SMTP Port', 'xss_clean');
        $this->form_validation->set_rules('smtp_user', 'SMTP Username', 'xss_clean');
        $this->form_validation->set_rules('receive_email', 'Receiver Email', 'required|xss_clean');
        //$this->form_validation->set_rules('smtp_pass', 'SMTP Password', 'required|xss_clean');
        //$this->form_validation->set_rules('charset', 'CharSet', 'required|xss_clean');

        if ($this->form_validation->run() == FALSE) {
            $data['info'] = $this->settings_model->get_email_settings();
            $data['main'] = 'admin/email_settings';

            $data['title'] = 'Email Settings';
            $this->load->view('admin/admin', $data);
        } else {
            if($this->settings_model->update_email_settings()) {
                $this->session->set_userdata( 'flash_msg_type', "success" );
                $this->session->set_flashdata('flash_msg', 'Email Settings Updated Successfully');
                redirect(ADMIN_PATH . '/settings/email_settings','refresh');
            } else {
                $this->session->set_userdata( 'flash_msg_type', "danger" );
                $this->session->set_flashdata('flash_msg', 'Sorry, Unable to Update Email Settings');
                redirect(ADMIN_PATH . '/settings/email_settings', 'refresh');
            }

        }

    }

}
