<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		// cek login
		is_logged_in();
	}

	public function index()
	{
		$data['title'] = 'My Profile';
		$email = $this->session->userdata('email');
		$data['user'] = $this->user->getUserByEmail($email);

		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('user/index', $data);
		$this->load->view('templates/footer', $data);
	}

	public function edit()
	{
		$data['title'] = 'Edit Profile';
		$email = $this->session->userdata('email');
		$data['user'] = $this->user->getUserByEmail($email);

		$this->form_validation->set_rules('name', 'Full Name', 'trim|required');

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/edit', $data);
			$this->load->view('templates/footer');			
		} else {
			$name = $this->input->post('name');
			$email = $this->input->post('email');

			// cek jika ada gambar yang akan di upload
			$upload_image = $_FILES['image']['name'];

			if ($upload_image) {
				$config['upload_path'] = './assets/admin/img/profile/';
				$config['allowed_types'] = 'gif|jpg|png';
				$config['max_size']  = '11000';
				
				$this->load->library('upload', $config);
				
				if ( $this->upload->do_upload('image') ) {
					$old_image = $data['user']['image'];
					if ($old_image != 'default.jpg') {
						unlink(FCPATH . 'assets/admin/img/profile/' . $old_image);
					}
					$new_image = $this->upload->data('file_name');
					$this->db->set('image', $new_image);
				} else {
					echo $this->upload->display_errors();
				}
			}

			$this->db->set('name', $name);
			$this->db->where('email', $email);
			$this->db->update('user');

			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Your profile has been updated!</div>');
			redirect('user');
		}
	}

	public function changePassword()
	{
		$data['title'] = 'Change Password';
		$email = $this->session->userdata('email');
		$data['user'] = $this->user->getUserByEmail($email);

		$this->form_validation->set_rules('current_password', 'Current Password', 'trim|required');
		$this->form_validation->set_rules('new_password1', 'New Password', 'trim|required|min_length[3]|matches[new_password2]', [
			'matches' => 'Password dont matches!',
			'min_length' => 'Password too short!'
		]);
		$this->form_validation->set_rules('new_password2', 'Confirm New Password', 'trim|required|min_length[3]|matches[new_password1]', [
			'matches' => 'Password dont matches!',
			'min_length' => 'Password too short!'
		]);


		if ($this->form_validation->run() == FALSE) {
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/changepassword', $data);
			$this->load->view('templates/footer', $data);
		} else {
			$current_password = $this->input->post('current_password');
			$new_password = $this->input->post('new_password1');
			if (!password_verify($current_password, $data['user']['password'])) {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Wrong current password!</div>');
				redirect('user/changepassword');
			} else {
				if ($current_password == $new_password) {
					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">New password cannot be the same as current password!</div>');
					redirect('user/changepassword');
				} else {
					// password sudah ok
					$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

					$this->db->set('password', $password_hash);
					$this->db->where('email', $this->session->userdata('email'));
					$this->db->update('user');

					$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password changed!</div>');
					redirect('user/changepassword');
				}
			}
		}
	}

}