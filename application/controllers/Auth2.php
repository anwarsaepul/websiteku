<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
	}

	public function index()
	{
		if ($this->session->userdata('email')) {
			redirect('user');
		}
		// Membuat Rules
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
		$this->form_validation->set_rules('password', 'password', 'required|trim');
		// Membuat Validasi
		if ($this->form_validation->run() == false) {
			$data['title'] = 'Login Page';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/login');
			$this->load->view('templates/auth_footer');
		}else{
			// Ketika Validasinya lolos
			$this->_login();
		}
		
	}

	private function _login()
	{
		// Mengambil inputan email
		$email = $this->input->post('email');
		// Mengambil inputan password
		$password = $this->input->post('password');

		// Mengambil Query dari DB
		$user = $this->db->get_where('user', ['email' => $email])->row_array();
		
		// cek data user di db
		// Usernya ada
		if ($user) {
			// Jika usernya aktif
			if ($user['is_active'] == 1) {
				// cek password
				// jika berhasil
				if (password_verify($password, $user['password'])) {
					// Mengambil data login user
					$data = [
						'email' 	=> $user['email'],
						'role_id'	=> $user['role_id']
						];
					// Menyimpan ke dalam session
					$this->session->set_userdata($data);
					// mengarahkan user sebagai admin atau member
					if ($user['role_id'] == 1) {
						redirect('admin');
					} else{
						redirect('user');
					}


				}else{
					// Jika gagal
					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Wrong password!</div>');
				redirect('auth');
				}
				
			}else{
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">This email has not been activated!</div>');
				redirect('auth');
			}
		}else{
			// Jika tidak ada
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email is not registred!</div>');
			redirect('auth');
		}
	}

	public function registration()
	{
		// mengecek apakah ada session atau tidak
		if ($this->session->userdata('email')) {
			redirect('user');
		}
		// Membuat Rules
		$this->form_validation->set_rules('name', 'Name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]',[
				'is_unique'	=> 'This email has already registered!'
		]); //
		// user.email (nmaTabel.nmaField) di db
		$this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[4]|matches[password2]', [
				'matches' 		=> 'password dont matches!',
				'min_length'	=> 'Password too short!'
		]);
		$this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');

		if ($this->form_validation->run() == false) {
			$data['title'] = 'Registration';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/registration');
			$this->load->view('templates/auth_footer');
		}else{
			$email = $this->input->post('email', true);
			// Menyimpan Data ke db
			$data = [
				'name'		=> htmlspecialchars($this->input->post('name', true)),
				'email'		=> htmlspecialchars($email),
				'image'		=> 'default.jpg',
				'password'	=> password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
				'role_id'	=> 2,
				'is_active'	=> 0,
				'date_created' => time()
			];

			// Siapkan Token
			$token = base64_encode(random_bytes(32));
			$user_token = [
				'email'	=> $email,
				'token'	=> $token,
				'date_created' => time()
				];

			// $this->db->insert('user', $data);
			$this->db->insert('user_token', $user_token);

			// mengirim kode vertif ke email yg sudah registrasi
			$this->_sendEmail($token, 'verify'); 

			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Congratulation! your account has been created. please activate your account</div>');
			redirect('auth');
		}
	}

	private function _sendEmail($token, $type)
	{
		$config = [
			'protocol'	=> 'smtp',
			'smtp_host'	=> 'ssl://smtp.googlemail.com',
			'smtp_user'	=> 'zonaprogramming@gmail.com',
			'smtp_pass' => '1478963214789',
			'smtp_port' =>	465, 
			'mailtype'	=> 'html',
			'charset'	=> 'utf-8',
			'newline'	=> "\r\n"
		];

		$this->load->library('email', $config);
		$this->email->initialize($config);
        // $this->email->set_newline("\r\n");

        // $config = array();
        // $config['protocol'] = 'smtp';
        // $config['smtp_host'] = 'ssl://smtp.googlemail.com';
        // $config['smtp_user'] = 'zonaprogramming@gmail.com';
        // $config['smtp_pass'] = '1478963214789';
        // $config['smtp_port'] = 465;
        // $config['mailtype'] = 'html';
        // $config['charset'] = 'utf-8';

		// $this->load->library('email', $config);
  //       $this->email->initialize($config);
  //       $this->email->set_newline("\r\n");


		// Memanggil Librarry di CI
		// $this->load->library('email', $config);

		// Menyiapkan Email
		$this->email->from('zonaprogramming@gmail.com', 'Zona Programming');
		$this->email->to($this->input->post('email'));
		// $this->email->to('persikami@gmail.com');
		$this->email->subject('Account verification');
		$this->email->message('Hello World');

		if ($type == 'verify') {
			
			$this->email->subject('Account Verification');
			$this->email->message('click this link to verify your account : <a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">activated</a>');
		}else if ($type == 'forgot') {
			
			$this->email->subject('Reset Password');
			$this->email->message('click this link to reset your password : <a href="'. base_url() . 'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">Reset Password</a>');
		}

		if ($this->email->send()){
				return true;
		}else{
			echo $this->email->print_debugger();
			die;
		}

	}


	public function verify()
	{
		// Mengambil data email & token
		$email = $this->input->get('email');
		$token = $this->input->get('token');

		// memastikan data email valid atau ada di db
		$user = $this->db->get_where('user', ['email' => $email])->row_array();

		// cek email
		if ($user) {
			// cek kode token di db
			$user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
			// cek kode token di link
			if ($user_token) {
				// cek expired token
				if (time() - $user_token['date_created'] < (60*60*48)) {
					// jika sudah benar update table user active nya
					$this->db->set('is_active', 1);
					$this->db->where('email', $email);
					$this->db->update('user');

					// lalu hapus tokennya d tb user_token
					$this->db->delete('user_token', ['email' => $email]);

					$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">' . $email . ' has been activated! please login . </div>');
					redirect('auth');
				}else{
				// jika token lebih dari 2 hari tdk di klik maka menghapus data user & token di db
				$this->db->delete('user', ['email' => $email]);
				$this->db->delete('user_token', ['email' => $email]);
				// tampilkan pesan error
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account activation failed! token expired.</div>');
				redirect('auth');
				}
			}else{
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account activation failed! invalid token</div>');
			redirect('auth');
			}
		}else{
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account activation failed! wrong email</div>');
			redirect('auth');
		}
	}



	public function logout()
	{
		$this->session->unset_userdata('email');
		$this->session->unset_userdata('role_id');

		$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">You have been logged out!</div>');
		redirect('auth');

	}

	public function blocked()
	{
		$this->load->view('auth/blocked');
	}

	public function forgotPassword()
	{
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
		if ($this->form_validation->run() == false) {
			$data['title'] = 'Forgot Password';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/forgotPassword');
			$this->load->view('templates/auth_footer');
		}else{
			$email = $this->input->post('email');
			// Cek email di db
			$user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

			if ($user) {
				// jika emailnya ada di db buat kode token
				// Siapkan Token
				$token = base64_encode(random_bytes(32));
				$user_token = [
					'email'	=> $email,
					'token'	=> $token,
					'date_created' => time()
					];

				$this->db->insert('user_token', $user_token);
				$this->_sendEmail($token, 'forgot');

				$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">please cek your email to reset your password!</div>');
				redirect('auth/forgotpassword');


			}else{
				// jika emailnya tdk ada di db
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email is not registered or activated!</div>');
				redirect('auth/forgotpassword');
			}

		}
	}


	public function resetPassword()
	{// Mengambil data email & token
		$email = $this->input->get('email');
		$token = $this->input->get('token');

		// memastikan data email valid atau ada di db
		$user = $this->db->get_where('user', ['email' => $email])->row_array();

		// cek email
		if ($user) {
			// cek kode token di db
			$user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
			// cek kode token di link
			if ($user_token) {
				// Membuat session reset email
				$this->session->set_userdata('reset_email', $email);
				// jika benar maka tampilkan
				$this->changePassword();
				
			}else{
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password failed! invalid token</div>');
			redirect('auth');
			}
		}else{
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password failed! wrong email</div>');
			redirect('auth/forgotpassword');
		}
	}


	public function changePassword()
	{
		// cek sesion nya
		if (!$this->session->userdata('reset_email')) {
			// jika tdk ada tendang ke
			redirect('auth');
		}
		$this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[4]|matches[password2]');
		$this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');

		if ($this->form_validation->run() == false) {
			$data['title'] = 'Change Password';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/changePassword');
			$this->load->view('templates/auth_footer');
		}else{
			$password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
			$email = $this->session->userdata('reset_email');

			// edit table user
			$this->db->set('password', $password);
			$this->db->where('email', $email);
			$this->db->update('user');


			// Menghapus session
			$this->session->unset_userdata('reset_email');

			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">password has been changed! Please login.</div>');
				redirect('auth');

		}
	}
}