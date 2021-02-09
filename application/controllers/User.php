<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller 
{
	// Fungsi ketika CI_Controller di akses
	public function __construct()
	{
		parent:: __construct();
		is_logged_in();
	}

	public function index()
	{
		$data['title'] = 'My Profile';
		// Mengambil data dari session
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('user/index', $data);
		$this->load->view('templates/footer');
	}

	public function edit()
	{
		$data['title'] = 'Edit Profile';
		// Mengambil data dari session
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		$this->form_validation->set_rules('name', 'Full Name', 'required|trim');

		if($this->form_validation->run() == false) {		
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/edit', $data);
			$this->load->view('templates/footer');
		}else{
			$name = $this->input->post('name');
			$email = $this->input->post('email');

			// Cek Jika Ada gambar yg akan di upload
			$upload_image = $_FILES['image']['name'];

			// Cek file yg di upload
			if ($upload_image) {
				$config['allowed_types'] = 'gif|jpg|png';
				$config['max_size'] = '2048';
				$config['upload_path'] = './assets/img/profile/';

				$this->load->library('upload', $config);

				// jika berhasil
				if ($this->upload->do_upload('image')) {
					// cek nama gambar sebelumnya
					$old_image = $data['user']['image'];
					// jika gambar sebelumnya tidak sama dengan maka
					if ($old_image != 'default.jpg') {
						// hapus gambar sebelumnya
						unlink(FCPATH . 'assets/img/profile/' . $old_image);
					}


					$new_image = $this->upload->data('file_name');
					$this->db->set('image', $new_image);
				}else{
					// jika gagal
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
		// Mengambil data dari session
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		$this->form_validation->set_rules('current_password', 'Current Password', 'required|trim');
		$this->form_validation->set_rules('new_password1', 'New Password', 'required|trim|min_length[4]|matches[new_password2]');
		$this->form_validation->set_rules('new_password2', 'Confirm New Password', 'required|trim|min_length[4]|matches[new_password1]');

		if ($this->form_validation->run() == false) {

			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/changepassword', $data);
			$this->load->view('templates/footer');
		}else{
			$current_password = $this->input->post('current_password');
			$new_password = $this->input->post('new_password1');
			if (!password_verify($current_password, $data['user']['password'])) {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Wrong current password!</div>');
				redirect('user/changepassword');
			}else{
				// cek pw baru sama tdk dgn password sblm nya
				if ($current_password == $new_password) {
					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">New password cannot be the same as current password!</div>');
					redirect('user/changepassword');
				}else{
					// Password sudah okay
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
