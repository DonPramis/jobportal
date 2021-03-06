<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employer_profile extends CI_Controller {

	public function __construct()
	{
	  	parent::__construct();
        $this->load->model('employer_profile_model');
	  	$this->load->model('user_profile_model');
	  	$this->load->model('home_model');
        $this->load->helper('email_helper');
        $this->load->model('admin/settings_model');

        if(!$this->helper_model->validate_employer_session()){
          redirect(base_url());
        }
	}

	public function index() {
		$this->employer_details();
	}


	public function change_password() {
		$this->load->model('user_profile_model');
		$this->form_validation->set_rules('cur_password', 'Current Password', 'required|xss_clean|callback_verify_current_pass');
        $this->form_validation->set_rules('new_password', 'Password', 'required|xss_clean|min_length[6]|max_length[64]');
        $this->form_validation->set_rules('c_password', 'Confirm Password', 'required|xss_clean|matches[new_password]');

        if ($this->form_validation->run() == FALSE) {
        	$data['controller'] = 'employer_profile';
            $data["page"] = "member/change_password";
            $data["title"] = "Change Password";
            $this->template->__set('title', 'Change Password');
            $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
            $this->template->publish('user_layout');
        } else {
            $password = $this->helper_model->encrypt_me($this->input->post('new_password'));
            if($this->user_profile_model->update_password($password)) {
            	$this->session->set_userdata('user_pw', $password);
                $this->session->set_userdata( 'user_flash_msg_type', "success" );
                $this->session->set_flashdata('user_flash_msg', 'Password Changed Successfully');
                redirect(base_url().'employer_profile/employer_details', 'refresh');
            } else {
                $this->session->set_userdata( 'user_flash_msg_type', "danger" );
                $this->session->set_flashdata('user_flash_msg', 'Sorry, Unable to Change the Password');
                redirect(base_url().'employer_profile/change_password', 'refresh');
            }
        }
	}


	public function verify_current_pass() {
        if($this->user_profile_model->verify_current_pw()) {
            return true;
        } else {
            $this->form_validation->set_message('verify_current_pass','Current Password Incorrect');
            return false;
        }
    }


    function employer_details() {
        $data["details"] = $this->employer_profile_model->get_employer_by_id($this->session->userdata('user_id'));
        $data["jobs"] = $this->helper_model->get_jobs_by_employer_id($this->session->userdata('user_id'));
        $data["page"] = "member/employer/employer_details";
        $this->template->__set('title', 'Your Profile');
        $data['title'] = 'Your Profile';
        $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
        $this->template->publish("user_layout");
    }

    public function update_profile(){
        $id = $this->session->userdata('user_id');
        $this->form_validation->set_rules('website', "Website","xss_clean|trim|valid_url");
        $this->form_validation->set_rules('f_name', 'Company Name','required|xss_clean|trim');
        $this->form_validation->set_rules('dob_estd', 'Date of Establishment','trim|required|xss_clean');
        $this->form_validation->set_rules('address', "Address",'trim|required|xss_clean');
        $this->form_validation->set_rules('phone', "Phone",'trim|required|xss_clean|regex_match[/^[0-9]{10}$/]');
        $this->form_validation->set_rules('prev_image', 'Preview Image', 'xss_clean');
        $this->form_validation->set_rules('image', 'Image', 'xss_clean|callback__validate_image['.true.']');
        
        if($this->form_validation->run()==FALSE) {
            if(isset($_POST['post_image'])){
                if (file_exists("./uploads/user/images/" . $_POST['post_image'])){
                    @unlink("./uploads/user/images/" . $_POST['post_image']);
                }
            }
            
            $data["user_detail"] = $this->user_profile_model->get_user_detail($id);
            $data["page"] = "member/employer/update_employer_details";
            $this->template->__set('title', 'Update Profile');
            $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
            $this->template->publish('user_layout');
        } else {
            if (isset($_POST['post_image'])) {
                $image = $_POST['post_image'];
                if (file_exists("./uploads/user/images/" . $this->input->post('prev_image'))){
                    @unlink("./uploads/user/images/" . $this->input->post('prev_image'));
                }
            } else {
                $image = $this->input->post('prev_image');
            }
            if($this->user_profile_model->update_user_detail($image, $id)) {
                $this->session->set_userdata('user_flash_msg_type', "success" );
                $this->session->set_flashdata('user_flash_msg', 'Profile Updated Successfully');
                $this->index();
            } else {
                $this->session->set_userdata( 'user_flash_msg_type', "danger" );
                $this->session->set_flashdata('user_flash_msg', 'Sorry, Unable to Update Profile');
                $this->index();
            }
        }
    }

    public function post_job_view($procedure="") {
        $data['categories'] = $this->helper_model->get_category();
        $data['temp_procedure'] = $procedure;
        $data["page"] = "member/employer/job/post_job";
        $data["title"] = "post_job";
        $this->template->__set('title', 'Post Job');
        $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
        $this->template->publish('user_layout');
    }

    public function post_job() {
        $temp_procedure = $this->input->post('application_procedure');
        $procedure_data = isset($temp_procedure)?implode(",", $temp_procedure):'';

        $this->form_validation->set_rules('title', "Title",'required|xss_clean');
        $this->form_validation->set_rules('position', "Position",'required|xss_clean');
        $this->form_validation->set_rules('openings', "Openings",'required|xss_clean');
        $this->form_validation->set_rules('location', "Location",'required|xss_clean');
        $this->form_validation->set_rules('qualification', "Qualification",'required|xss_clean');
        $this->form_validation->set_rules('experience', "Experience",'required|xss_clean');
        $this->form_validation->set_rules('salary', "Salary",'required|xss_clean');
        $this->form_validation->set_rules('category_id', "Category",'required|xss_clean');
        $this->form_validation->set_rules('job_description', "Job Description",'required|xss_clean');
        $this->form_validation->set_rules('requirements', "Requirement",'required|xss_clean');
        $this->form_validation->set_rules('facilities', "Facilities",'required|xss_clean');
        $this->form_validation->set_rules('deadline_date', "Deadline Date",'required');
        $this->form_validation->set_rules('application_procedure', "Application Procedure",'callback__validate_checkbox|xss_clean');
        
        if($this->form_validation->run()==FALSE) {
            if($temp_procedure) {
                $this->post_job_view($procedure_data);
            } else {
                $this->post_job_view();
            }
        } else {
            if($this->employer_profile_model->post_job($procedure_data)) {
                $this->session->set_userdata( 'user_flash_msg_type', "success" );
                $this->session->set_flashdata('user_flash_msg', 'Job Posted Sucessfully');
                redirect('employer_profile/index');
            } else {
                $this->session->set_userdata( 'user_flash_msg_type', "danger" );
                $this->session->set_flashdata('user_flash_msg', 'Sorry, Unable to Post Job');
                $this->index();
            }
        }
    }


    function job() {
        $data['jobs'] = $this->employer_profile_model->get_all_jobs_by_employer_id($this->session->userdata('user_id'));
        $data["page"] = "member/employer/job/list";
        $data["title"] = 'Jobs You Have Posted';
        $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
        $this->template->publish('user_layout');
    }

    public function search() {
        $data = array(
            'search' => $this->input->get('search')
        );
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('search', 'Search', 'required|xss_clean');
        if($this->form_validation->run() == false) {
            if($this->session->userdata('referred_from')) {
                redirect($this->session->userdata('referred_from'));
            } else {
                redirect(base_url().'employer_profile', 'refresh');
            }
            
        } else {
            $this->load->library('pagination');
            if(count($_GET) > 0) {
                $config['suffix'] = '?' . http_build_query($_GET, '', "&");
            }
            $config['base_url'] = base_url()."employer_profile";
            $config['first_url'] = $config['base_url'].'?'.http_build_query($_GET);
            $config['total_rows'] = $this->employer_profile_model->get_search_result(null, null, true);
            $config['per_page'] = 10; //$this->config->item('products_per_page');
            $config['num_links'] = 5;
            $config['full_tag_open'] = "<ul class='pagination'>";
            $config['full_tag_close'] ="</ul>";
            $config['num_tag_open'] = '<li>';
            $config['num_tag_close'] = '</li>';
            $config['cur_tag_open'] = "<li class='disabled'><li class='active'><a href='javascript:void(0)'>";
            $config['cur_tag_close'] = "<span class='sr-only'></span></a></li>";
            $config['next_tag_open'] = "<li>";
            $config['next_tag_close'] = "</li>";
            $config['prev_tag_open'] = "<li>";
            $config['prev_tag_close'] = "</li>";
            $config['first_tag_open'] = "<li>";
            $config['first_tag_close'] = "</li>";
            $config['last_tag_open'] = "<li>";
            $config['last_tag_close'] = "</li>";

            $this->pagination->initialize($config);

            $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
            $data["search_results"] = $this->employer_profile_model->get_search_result(null, null);
            $data['pagination_links'] = $this->pagination->create_links();
            $data['page_title'] = "Search results for '" . $this->input->get('search') ."'";
            $data["page"] = 'member/employer/jobseeker_search';
            $data["title"] = $this->input->get('search');
            $data["num_rows"] = $config['total_rows'];
            $this->template->__set('title', $this->input->get('search'));
            $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
            $this->template->publish('user_layout');
        }
    }


    function delete_job($job_id) {
        $data['job'] = $this->employer_profile_model->get_job_by_id($job_id);
        if($data['job']['user_id'] != $this->session->userdata('user_id')) { //$this->session->userdata('user_id')
            echo json_encode(array(
                    'response' => FALSE,
                    'message' => "The job can't be deleted. Please try again later."
                ));
        } else {
            $table = 'tbl_jobs';
            $this->helper_model->delete_from_table($job_id, $table);
            echo json_encode(array(
                    'response' => TRUE,
                ));
        }
    }


    function edit_job($id) {
        $data["job"] = $this->employer_profile_model->get_job_by_id($id);
        if($data['job']['user_id'] != $this->session->userdata('user_id')) {
            show_404();
            exit;
        }

        $data['categories'] = $this->helper_model->get_category();

        $temp_procedure = $this->input->post('application_procedure');
        $data['temp_procedure'] = $temp_procedure?implode(",", $temp_procedure):$data['job']['application_procedure'];

        $this->form_validation->set_rules('title', "Title",'required|xss_clean');
        $this->form_validation->set_rules('position', "Position",'required|xss_clean');
        $this->form_validation->set_rules('openings', "Openings",'required|xss_clean');
        $this->form_validation->set_rules('location', "Location",'required|xss_clean');
        $this->form_validation->set_rules('qualification', "Qualification",'required|xss_clean');
        $this->form_validation->set_rules('experience', "Experience",'required|xss_clean');
        $this->form_validation->set_rules('salary', "Salary",'required|xss_clean');
        $this->form_validation->set_rules('category_id', "Category",'required|xss_clean');
        $this->form_validation->set_rules('job_description', "Job Description",'required|xss_clean');
        $this->form_validation->set_rules('requirements', "Requirement",'required|xss_clean');
        $this->form_validation->set_rules('facilities', "Facilities",'required|xss_clean');
        $this->form_validation->set_rules('deadline_date', "Deadline Date",'required');
        $this->form_validation->set_rules('application_procedure', "Application Procedure",'xss_clean|callback__validate_checkbox');
        
        if($this->form_validation->run()==FALSE) {
            $data["page"] = "member/employer/job/edit_job";
            $data["title"] = "Edit Job";
            $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
            $this->template->publish('user_layout');

        } else {
            if($this->user_profile_model->update_qualification($id,$this->session->userdata('user_id'))) {
                $this->session->set_userdata( 'user_flash_msg_type', "success" );
                $this->session->set_flashdata('user_flash_msg', 'Qualification Edited Successfully');
                redirect('user_profile/qualification');
            } else {
                $this->session->set_userdata( 'user_flash_msg_type', "danger" );
                $this->session->set_flashdata('user_flash_msg', 'Sorry, Unable to Edited Qualification');
                $data["page"] = "member/jobseeker/qualification/edit";
                $data["title"] = "Edit Qualification";
                $this->template->partial->view("default_layout", $data, $overwrite=FALSE);
                $this->template->publish('user_layout');
            }
        }
    }

    
    function notifications() {
        $user_id = $this->session->userdata('user_id');
        $data['data'] = $this->employer_profile_model->get_all_applied_jobs($user_id);
        $data["title"] = "Applied Jobs";
        $data["page"] = "member/employer/employer_notification";
        $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
        $this->template->publish('user_layout');
    }


    function user_details($id){
        $arrData = $this->employer_profile_model->get_map_job_details($id);
        $this->employer_profile_model->update_read_flag_status($arrData['id']);
        $this->jobseeker($arrData['user_id']);
    }
    
    function _validate_image($image='', $edit=false) {
        if(isset($_FILES['image']) && !empty($_FILES['image']['name'])) {     //check if the field is empty or not
            $image = array(
                'location' => './uploads/user/images/',
                'temp_location' => './uploads/user/images/temp/',
                'width' => USER_W,
                'height' => USER_H,
                        'image' => 'image'      //field name of the file in the form
                        );
            $this->load->helper('image_helper');
            $response = validate_image($image);
            if($response['status']) {
                return true;
            } else {
                $this->form_validation->set_message('_validate_image', $response['msg']);
                return false;
            }
        } elseif(!$edit) {
            $this->form_validation->set_message('_validate_image', 'Please select an image for logo.');
            return false;
        } else {
            return true;
        }
    }


    function jobseeker($jobseeker_id='') {
        if($jobseeker_id==''){
            show_404(); exit;
        }
        $this->load->model('user_profile_model');
        $data['jobseeker'] = $this->user_profile_model->get_jobseeker_details($jobseeker_id);
        if(count($data['jobseeker']) < 1){
            show_404(); exit;
        }
        $data['qualifications'] = $this->user_profile_model->get_jobseeker_qualification($jobseeker_id);
        $data['experiences'] = $this->user_profile_model->get_jobseeker_experience($jobseeker_id);
        $data['tags'] = $this->employer_profile_model->get_jobseeker_tags($jobseeker_id);
        $data["page"] = "member/employer/jobseeker_details";
        $data["title"] = $data['jobseeker']['f_name'].' '.$data['jobseeker']['f_name'].' - Details';
        $this->template->__set('title', $data['jobseeker']['f_name'].' '.$data['jobseeker']['f_name'].' - Details');
        $this->template->partial->view("user_layout", $data, $overwrite=FALSE);
        $this->template->publish('user_layout');
    }


    public function send_email() {
        $this->form_validation->set_rules('receiver_email', 'Receiver', 'required|xss_clean|valid_email|callback_validate_receiver');
        $this->form_validation->set_rules('subject', 'Subject', 'required|xss_clean');
        $this->form_validation->set_rules('content', 'Content', 'required|xss_clean');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(array(
                'error_title' => 'validation_error',
                'error_description' => "<p style='color:red'>Please fill all the required fields correctly.</p>",
                'subject' => form_error('subject'),
                'content' => form_error('content'),
                'receiver_email' => form_error('receiver_email'),

            ));
        } else {
            $this->load->model('admin/settings_model');
            $mail_settings = $this->settings_model->get_email_settings();
            $mail_params = array(
                        'from' => $this->session->userdata('user_email'),
                        'from_name' => $this->session->userdata('name'),
                        'to' => $this->input->post('receiver_email'),
                        'subject' => $this->input->post('subject'),
                        'message' => $this->input->post('content'),
                );
            if(send_email($mail_settings, $mail_params)) {
                echo json_encode(array(
                    'error_msg' => 'Email Sent Successfully.',
                    'error_title' => 'success'
                ));
            } else {
                echo json_encode(array(
                    'error_msg' => 'Email sending failed. Please try again later.',
                    'error_title' => 'email_error'
                ));
            }
        }

    }


    function validate_receiver() {
        if($this->employer_profile_model->verify_receiver()){
            return true;
        } else {
            $this->form_validation->set_message('validate_receiver', 'Incorrect Receiver Email');
            return false;
        }
    }

    function _validate_checkbox() {
        if(count($this->input->post('application_procedure'))==0){
            $this->form_validation->set_message('_validate_checkbox','Application Procedure is required');
            return false;
        } else{
            return true;
        }
    }

    function select_for_job() {
        if(isset($_POST['job_id']) && !empty($_POST['job_id']) && isset($_POST['jobseeker_id']) && !empty($_POST['jobseeker_id'])){
            $job_id = $_POST['job_id'];
            $jobseeker_id = $_POST['jobseeker_id'];
            $employer_id = $this->session->userdata('user_id');
            $userEmail = $this->employer_profile_model->getUserEmail($jobseeker_id);
            $data = $this->employer_profile_model->update_notify_status($job_id,$employer_id,$jobseeker_id);
            if($data){
                $this->sendJobNotification($userEmail);
                echo json_encode(array('success'=>true));
            } else {
                echo json_encode(array('success'=>false));
            }
        } else {
            echo json_encode(array('success'=>false));
        }
    }

    // send mail to jobbseeker to notify he/she is selected for the job
    private function sendJobNotification($email) {
        $mail_setting = $this->settings_model->get_email_settings();
        $message = $this->settings_model->get_email_template('NOTIFY_USER');
        $subject = $message['subject'];
        $emailbody = $message['content'];
        
        $parseElement = array(
            "USERNAME" => $email,
            "SITENAME" => 'JobPortal',
            "SITELINK" => base_url()
        );
        $subject = parse_email($parseElement, $subject);
        $emailbody = parse_email($parseElement, $emailbody);
        $mail_params = array(
                        'to' => $email,
                        'subject' => $subject,
                        'message' => $emailbody,
                );
        if(send_email($mail_setting, $mail_params)){
            return true;
        } else {
            return false;
        }

    }

}