<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller 
{
	// Fungsi ketika CI_Controller di akses
	public function __construct()
	{
		parent:: __construct();
		is_logged_in();
	}

	public function index()
	{
		$data['title'] = 'Dashboard';
		// Mengambil data dari session
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('admin/index', $data);
		$this->load->view('templates/footer');
	}

	public function role()
	{
		$data['title'] = 'Role';
		// Mengambil data dari session
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		$data['role'] = $this->db->get('user_role')->result_array();

		// Membuat Rules nya
		$this->form_validation->set_rules('role', 'Role', 'required');

		if ($this->form_validation->run() == false) {
		
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('admin/role', $data);
			$this->load->view('templates/footer');
		}else{
			// jika berhasil input data baru
			$this->db->insert('user_role',['role' => $this->input->post('role')]);
			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">New role added!</div>');
			redirect('admin/role');
		}

	}

	public function roleAccess($role_id)
	{
		$data['title'] = 'Role Access';
		// Mengambil data dari session
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		$data['role'] = $this->db->get_where('user_role', ['id' => $role_id])->row_array();

		$this->db->where('id !=', 1);

		$data['menu'] = $this->db->get('user_menu')->result_array();
		
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('admin/roleaccess', $data);
		$this->load->view('templates/footer');
	}

	public function changeAccess()
	{
		$menu_Id = $this->input->post('menuId');
		$role_Id = $this->input->post('roleId');

		$data = [
			'role_id' => $role_Id,
			'menu_Id' => $menu_Id
		];

		$result = $this->db->get_where('user_access_menu', $data);

		if ($result->num_rows() < 1) {
			$this->db->insert('user_access_menu', $data);
		}else{
			$this->db->delete('user_access_menu', $data);
		}

		$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Access Changed!</div>');
	}
}
