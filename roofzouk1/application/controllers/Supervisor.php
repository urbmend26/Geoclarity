<?php
class Supervisor extends CI_Controller {

	function __construct()
    {
		parent::__construct();
		
		$this->load->library('form_validation');
		$this->load->library('session');
		$this->load->helper('url');
		
		$this->load->model('member');
		$this->load->model('task');
		$this->load->model('user');
		/*
		$this->load->model('group');
		$this->load->model('theme');
		$this->load->model('question');
		*/
		
		// get sostask count 
		if (isset($_SESSION['supervisor'])) {
			$_SESSION['soscount'] = $this->task->GetCurrentSOSCount($_SESSION['supervisor']->supervisor_id);
		}
	}
	public function index()
	{
		if (!$this->checkLogin()) {
	 		redirect(  'supervisor/login', 'refresh');	  
	 	} else {
	 		redirect(  'supervisor/home', 'refresh');
	 	}
	}
	function checkLogin() {
		 $company = $this->supervisor = $this->session->userdata('supervisor');
		 if ($this->supervisor) {
		 	return true;
		 } else {
		 	return false;
		 }
	}
	public function login() {
		if($_SERVER['SERVER_PORT'] !== 443 &&
		   (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
		  header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		  exit;
		}
		
		$reqArray = array();
		$reqArray['loginError'] = "";
		
		$task = $this->input->post('task');
		if ($task == "login") {
			$ret = $this->member->loginSupervisor($_REQUEST['email'], $_REQUEST['pwd']);
			if ($ret!=false) {
				if ($ret->verifyemail == 0) {
					$reqArray['loginError'] = "You have to verify your account by your company.<br/> Please contact your company manager to verify your account.";
				} else {
		 			$this->session->set_userdata('supervisor', $ret);
		 			redirect(  'supervisor/home', 'refresh');
				}
	 		} else {
	 			$reqArray['loginError'] = "Email or Password is incorrect.";
	 		}
		} 
		$this->load->view('supervisor/login', $reqArray);
	}
	
	public function signup() {
		if($_SERVER['SERVER_PORT'] !== 443 &&
		   (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
		  header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		  exit;
		}
		
		$reqArray = array();
		$reqArray['registerError'] = "";
		$reqArray['registerSuccess'] = "";
		$reqArray['NotFoundCompany'] = "";
		$task = $this->input->post('task');
		if ($task == "register") {
			
			// check company_name 
			$companyName = $_REQUEST['companyName'];
			$company_id = $this->member->checkCompanyName($companyName);
			if ($company_id == 0 ) {
				$reqArray['registerError'] = "Your Company is not registered";
				$reqArray['NotFoundCompany'] = "yes";
				
			} else {
				
				$reqArray['email'] = $this->db->escape_str($_REQUEST['email']);
				$reqArray['supervisor_name'] = $this->db->escape_str($_REQUEST['Supervisorname']);
				$reqArray['department'] = $this->db->escape_str($_REQUEST['department']);
				$reqArray['teamsize']= $this->db->escape_str($_REQUEST['teamsize']);
				$reqArray['home_phone'] = $this->db->escape_str($_REQUEST['homePhone']);
				$reqArray['office_phone'] = $this->db->escape_str($_REQUEST['officePhone']);
				$reqArray['mobile_phone'] = $this->db->escape_str($_REQUEST['mobilephone']);
				$reqArray['address'] = $this->db->escape_str($_REQUEST['address']);
				$reqArray['pwd'] = $this->db->escape_str($_REQUEST['pwd']);
				$reqArray['company_id'] = $company_id;
				$token = md5(time());
				
				$result = $this->member->registerSupervisor($reqArray, $token);
				if ($result == "") {
					$reqArray['registerSuccess'] = "Thank you using our service.<br/>A verification email is sent to your company email.<br/>Your company manager have to verify your account.";
					
					// send a verification email to company email
					$companyInfo = $this->member->getCompanyInfoById($company_id);
					$mailTitle = "[Geoclarity] Verify your supervisor email.";
					$mailContent = "Dear ".$companyInfo->company_name."<br/>";
					$mailContent .= "Your SuperVisor information have successfully registered to our site.<br/><br/> ";
					
					$mailContent .= "New Supervisor Information<br/> ";
					$mailContent .= "Email: ".$reqArray['supervisor_name']."<br/>";
					$mailContent .= "Name: ".$reqArray['email']."<br/>";
					$mailContent .= "Department: ".$reqArray['department']."<br/>";
					$mailContent .= "Home phone: ".$reqArray['home_phone']."<br/>";
					$mailContent .= "Office phone: ".$reqArray['office_phone']."<br/>";
					$mailContent .= "Mobile phone: ".$reqArray['mobile_phone']."<br/>";
					$mailContent .= "Address: ".$reqArray['address']."<br/><br/>";
					
					
					$mailContent .= "Please verify your SuperVisor account by using below link. <br/>";
					$mailContent .="<a href='http://geoclarity.com/roofzouk/index.php/supervisor/verifyemail?token={$token}'>Verify Email</a>";
					
					$headers = "Content-type: text/html; charset=UTF-8\r\n" . "From: info@geoclarity.com";
					mail($companyInfo->company_email , $mailTitle, $mailContent, $headers);
					
				} else {
					$reqArray['registerError'] = $result;
				}	
			}
		} else if ($task == "company") {
			$contact = $_REQUEST['contact'];
			$mailTitle = "[Geoclarity] New company contact information.";
			
			$headers = "Content-type: text/html; charset=UTF-8\r\n" . "From: info@geoclarity.com";
			mail("contact@geoclarity.com", $mailTitle, $contact, $headers);
			$reqArray['registerSuccess'] = "Thank you for contacting us. We will contact you soon.";
		}
		$this->load->view('supervisor/signup', $reqArray);
	}
	public function forgotpassword() {
		$reqArray = array();
		$reqArray['loginError'] = "";
		$reqArray['msgSuccess'] = "";
		$task = $this->input->post('task');
		if ($task == "login") {
			$ret = $this->member->getSupervisorInfoByEmail($_REQUEST['email']);
			if ($ret!=null) {
				// reset password and send a email 
				$resetPwd = "123";
				$mailTitle = "[Geoclarity] Forgot your password.";
				$mailContent = "Dear ".$ret->supervisor_name."<br/>";
				$mailContent .= "Your password was changed to {$resetPwd}.<br/><br/> ";
				
				$headers = "Content-type: text/html; charset=UTF-8\r\n" . "From: info@geoclarity.com";
				mail($ret->email, $mailTitle, $mailContent, $headers);
				
				$this->member->setSupervisorPassword($ret->supervisor_id, $resetPwd);
				$reqArray['msgSuccess'] = "Success sent a email to your email address.";
	 		} else {
	 			$reqArray['loginError'] = "Cannot find supervisor information.";
	 		}
		} 
		$this->load->view('supervisor/findpwd', $reqArray);
	}
	public function verifyemail() {
		$reqArray['verifySuccess'] = "";
		$reqArray['verifyError'] = "";
		
		$token = $_REQUEST['token'];
		$token = $this->db->escape_str($token);
		$ret = $this->member->verifySupervisorEmail($token);
		if ($ret!=false) {
			if ($ret->verifyemail == 1) {
				$reqArray['verifyError'] = "You already verified your supervisor account";
			} else {
				$this->member->updateVerifySupervisorEmail($token);
	 			$reqArray['verifySuccess'] = "Successfully verified your supervisor account";
			}
 		} else {
 			$reqArray['verifyError'] = "Invalid access.";
 		}
		$this->load->view('supervisor/verifyemail', $reqArray);
	}
	public function logout() {
		$this->session->unset_userdata('supervisor');
		redirect(  'supervisor/login', 'refresh');
	} 
	public function home() {
		if (!$this->checkLogin()) redirect(  'supervisor/login', 'refresh');	  
		$this->load->view('supervisor/header');
		
		$reqArray['seltype'] = 0;
		
		$where = "";
		if (isset($_REQUEST['seltype'])) {
			$seltype = intval($_REQUEST['seltype']);
			//$seltypeList = implode(",", $seltypes);
			//foreach ($seltypeList as $seltype) {
				if ($seltype == 1) {
					$where .= " task_Status = 'DELAYED' ";
				} else if ($seltype == 2) {
					$where .= " task_Status = 'COMPLETED' ";
				} else if ($seltype == 3) {
					$where .= " SOS_STATUS = 'Yes' ";
				} else if ($seltype == 4) {
					$where .= " task_Status = 'CANCELLED' ";
				}
			//}
			$reqArray['seltype'] = $seltype; 
		}

		// find top 10 Supervisors 
		$reqArray['top10User'] = $this->member->getTopUsers($where, $_SESSION['supervisor']->supervisor_id);
		 
		$reqArray['top10UserCount'] = count($reqArray['top10User']);
		
		$tasks = $this->task->getAllSupervisorTasks($where, $_SESSION['supervisor']->supervisor_id);
		$jsonTasks = json_encode($tasks);
		$reqArray['jsonTasks'] = $jsonTasks;
		$UserList =$this->member->searchUsersBySupervisor("", $_SESSION['supervisor']->supervisor_id);
		$jsonUsers = json_encode($UserList);
		$reqArray['JsonUsers']=$jsonUsers;
		
		$this->load->view('supervisor/main', $reqArray);
	}
	
	public function profile() {
		$reqArray = array();
		if (isset($_REQUEST['Supervisorname'])) {
			$_SESSION['supervisor']->supervisor_name = $this->db->escape_str($_REQUEST['Supervisorname']);
			$_SESSION['supervisor']->department = $this->db->escape_str($_REQUEST['department']);
			$_SESSION['supervisor']->teamsize = $this->db->escape_str($_REQUEST['teamsize']);
			$_SESSION['supervisor']->home_phone = $this->db->escape_str($_REQUEST['homePhone']);
			$_SESSION['supervisor']->office_phone = $this->db->escape_str($_REQUEST['officePhone']);
			$_SESSION['supervisor']->mobile_phone = $this->db->escape_str($_REQUEST['mobilephone']);
			$_SESSION['supervisor']->address = $this->db->escape_str($_REQUEST['address']);
			$pwd =  $this->db->escape_str($_REQUEST['pwd']);
			 
			$this->member->modifySupervisor($pwd);
		}
		
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/profile', $reqArray);
	}
	
	public function upload() {
		$reqArray = array();
		$reqArray['taskSuccess'] = "";
		$reqArray['taskFailed'] = "";
		$reqArray['supSuccess'] = "";
		$reqArray['supFailed'] = "";
		$reqArray['userSuccess'] = "";
		$reqArray['userFailed'] = "";
		
		$task = $this->input->post('task');
		if ($task == "tasks") {
			include_once 'application/libraries/PHPExcel/PHPExcel.php';
			try {
				$inputFileName = $_FILES['file']['tmp_name']."";
				/**  Identify the type of $inputFileName  **/
				$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
				/**  Create a new Reader of the type that has been identified  **/
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objReader->setReadDataOnly(true);
				//$objReader->setReadFilter( new MyReadFilter() );
				/**  Load $inputFileName to a PHPExcel Object  **/
				$objPHPExcel = $objReader->load($inputFileName);
			} catch (Exception $e) {
				$reqArray['taskFailed'] = 'Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage();
			}
			if ($reqArray['taskFailed'] == "") {
				$importCnt = 0;
				$sheet = $objPHPExcel->getSheet(0);
				$highestRow = $sheet->getHighestRow(); 
				$highestColumn = $sheet->getHighestColumn();
	
				$itemList = array();
				for ($row = 1; $row <= $highestRow; $row++){ 
					try {
					$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
		                                    NULL,
		                                    TRUE,
		                                    FALSE);
		            } catch(Exception $e){
		            	continue;
		            }
		            
		            $info = $rowData[0];
		            if ($info[0] == "" || $info[1] == "" || $info[2] == "" || $info[3] == ""|| $info[4] == ""|| $info[5] == "") continue;
		            /*
		            $startCell = $sheet->getCell('G'.$row);
		            $startCellFormattedValue = trim($startCell->getFormattedValue());
					if (!empty($startCellFormattedValue)) {
					    $dateInTimestampValue = PHPExcel_Shared_Date::ExcelToPHP($startCell->getValue());
					    $dateInTimestampValue=$dateInTimestampValue;
					     $info[6] = date("Y-m-d H:i", $dateInTimestampValue); 
					}
					$endCell = $sheet->getCell('H'.$row);
		            $endCellFormattedValue = trim($endCell->getFormattedValue());
					if (!empty($endCellFormattedValue)) {
					    $dateInTimestampValue = PHPExcel_Shared_Date::ExcelToPHP($endCell->getValue());
					    $dateInTimestampValue=$dateInTimestampValue ;
					     $info[7] = date("Y-m-d H:i", $dateInTimestampValue); 
					}*/
		            $info[6] = date("Y-m-d H:i", strtotime($info[6]));
		            $info[7] = date("Y-m-d H:i", strtotime($info[7]));
		            // check user
		            $user_id = $this->member->FindUserByEmail($info[5]);
		            if ($user_id == 0) continue;
		            $info[5] = $user_id;
					
		            // check contact 
		            $contactInfo = $this->member->FindContact($info[1], $info[2], $info[3], $info[4]);
		            if ($contactInfo == NULL) {
		            	// find lat and long information
		            	
		            	$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$info[2]." ".$info[3]."&key=POPULATE_API_KEY";
		            	$url = str_replace(" ", "+", $url);
		            	$apiResult = file_get_contents($url);
		            	$apiJson = json_decode($apiResult);
		            	$lat = $apiJson->results[0]->geometry->location->lat;
		            	$long = $apiJson->results[0]->geometry->location->lng;
		            	
		            	//InsertContact($name, $address, $city, $phone, $lat, $long) {
		            	$this->member->InsertContact($info[1], $info[2], $info[3], $info[4], $lat, $long);
		            } else {
		            	$lat = $contactInfo->contact_lat;
		            	$long = $contactInfo->contact_lng;
		            }
		            
		            if ( $this->task->importTaskByCompany($info, $_SESSION['company']->company_id, $lat, $long) > 0) {
		            	$importCnt++;
		            }
				}
				//break;
			}
			$reqArray['taskSuccess'] = "Successfully imported {$importCnt} tasks.";
		} else if ($task == "supervisors") {
			include_once 'application/libraries/PHPExcel/PHPExcel.php';
			try {
				$inputFileName = $_FILES['file']['tmp_name']."";
				/**  Identify the type of $inputFileName  **/
				$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
				/**  Create a new Reader of the type that has been identified  **/
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objReader->setReadDataOnly(true);
				//$objReader->setReadFilter( new MyReadFilter() );
				/**  Load $inputFileName to a PHPExcel Object  **/
				$objPHPExcel = $objReader->load($inputFileName);
			} catch (Exception $e) {
				$reqArray['supFailed'] = 'Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage();
			}
			if ($reqArray['supFailed'] == "") {
				$importCnt = 0;
				$sheet = $objPHPExcel->getSheet(0);
				$highestRow = $sheet->getHighestRow(); 
				$highestColumn = $sheet->getHighestColumn();
	
				$itemList = array();
				for ($row = 1; $row <= $highestRow; $row++){ 
					try {
					$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
		                                    NULL,
		                                    TRUE,
		                                    FALSE);
		            } catch(Exception $e){
		            	continue;
		            }
		            
		            $info = $rowData[0];
		            if ($info[0] == "" || $info[1] == "" || $info[2] == "" || $info[3] == ""|| $info[4] == "") continue;
		            
		            if ( $this->member->importSupervisorByCompany($info, $_SESSION['company']->company_id > 0)) {
		            	$importCnt++;
		            }
				}
				//break;
			}
			$reqArray['supSuccess'] = "Successfully imported {$importCnt} Supervisor.";
			
			
		} else if ($task == "users") {
			include_once 'application/libraries/PHPExcel/PHPExcel.php';
			try {
				$inputFileName = $_FILES['file']['tmp_name']."";
				/**  Identify the type of $inputFileName  **/
				$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
				/**  Create a new Reader of the type that has been identified  **/
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objReader->setReadDataOnly(true);
				//$objReader->setReadFilter( new MyReadFilter() );
				/**  Load $inputFileName to a PHPExcel Object  **/
				$objPHPExcel = $objReader->load($inputFileName);
			} catch (Exception $e) {
				$reqArray['userFailed'] = 'Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage();
			}
			if ($reqArray['userFailed'] == "") {
				$importCnt = 0;
				$sheet = $objPHPExcel->getSheet(0);
				$highestRow = $sheet->getHighestRow(); 
				$highestColumn = $sheet->getHighestColumn();
	
				$itemList = array();
				for ($row = 1; $row <= $highestRow; $row++){ 
					try {
					$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
		                                    NULL,
		                                    TRUE,
		                                    FALSE);
		            } catch(Exception $e){
		            	continue;
		            }
		            
		            $info = $rowData[0];
		            if ($info[0] == "" || $info[1] == "" || $info[2] == "" || $info[3] == ""|| $info[4] == "") continue;
		            
		            if ( $this->member->importUserByCompany($info, $_SESSION['company']->company_id > 0)) {
		            	$importCnt++;
		            }
				}
				//break;
			}
			$reqArray['userSuccess'] = "Successfully imported {$importCnt} Users.";
		}
		
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/upload', $reqArray);
	}
	public function search() {
		$reqArray = array();
		$search = $this->db->escape_str($_REQUEST['q']);
		
		$reqArray['ContactList'] = $this->member->searchContacts($search, $_SESSION['supervisor']->company_id);
		$reqArray['TaskList'] = $this->task->searchTasksBySupervisor($search, $_SESSION['supervisor']->supervisor_id);
		$reqArray['UserList'] = $this->member->searchUsersBySupervisor($search, $_SESSION['supervisor']->supervisor_id);
		
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/search', $reqArray);
	}
	
	public function searchContact() {
		$search = $this->db->escape_str($_REQUEST['name']);
		$contact = $this->member->searchContactByName($search, $_SESSION['supervisor']->company_id);
		echo json_encode($contact);
	}
	public function getTasksCountByMap() {
		
		$lat1= $_REQUEST['lat1'];
		$lat2= $_REQUEST['lat2'];
		$long1= $_REQUEST['long1'];
		$long2= $_REQUEST['long2'];
		
		$where = "";
		$seltype = intval($_REQUEST['seltype']);
		if ($seltype == 1) {
			$where .= " task_Status = 'DELAYED' ";
		} else if ($seltype == 2) {
			$where .= " task_Status = 'COMPLETED' ";
		} else if ($seltype == 3) {
			$where .= " SOS_STATUS = 'Yes' ";
		} else if ($seltype == 4) {
			$where .= " task_Status = 'CANCELLED' ";
		}
			//}
			
		$retVal = array();
		$retVal['Created'] = $this->task->getSupervisorTaskCountByMap('CREATED', $lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		$retVal['Completed'] = $this->task->getSupervisorTaskCountByMap('COMPLETED', $lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		$retVal['Cancelled'] = $this->task->getSupervisorTaskCountByMap('CANCELLED', $lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		$retVal['Delayed'] = $this->task->getSupervisorTaskCountByMap('DELAYED', $lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		$retVal['Progress'] = $this->task->getSupervisorTaskCountByMap('INPROGRESS', $lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		$retVal['PendScheduled'] = $this->task->getSupervisorTaskCountByMap('PENDSCHEDULE', $lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		$retVal['ReScheduled'] = $this->task->getSupervisorTaskCountByMap('RESCHEDULED', $lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		$retVal['Sos'] = $this->task->getSupervisorTaskSOSCountByMap($lat1, $long1, $lat2, $long2, $where, $_SESSION['supervisor']->supervisor_id);
		echo json_encode($retVal);
		exit();	
	}
	
	// start section for search tasks for any region
	public function getRegions() {
		$search = $this->db->escape_str($_REQUEST['region']);
		$list = $this->task->searchRegionsTasksBySupervisor($search, $_SESSION['supervisor']->supervisor_id);
		echo json_encode($list);
		exit();
	}
	
	public function Region() {
		$reqArray = array();
		$postcode = $this->db->escape_str($_REQUEST['postcode']);
		$reqArray['postcode'] = $postcode;
		$reqArray['region'] = $this->task->getRegionNameByPostcode($postcode);
		
		$where = "postcode = '{$postcode}'";
		$tasks = $this->task->getAllTasksBySupervisor($where, $_SESSION['supervisor']->supervisor_id);

		$jsonTasks = json_encode($tasks);
		$reqArray['jsonTasks'] = $jsonTasks;
		
		$reqArray['userList'] = $this->task->getUserListByPostcode($postcode, $_SESSION['supervisor']->supervisor_id);
		//print_r($reqArray['supList']);
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/region', $reqArray);
	}
	
	// start section for search user by name
	public function getUsers() {
		$search = $this->db->escape_str($_REQUEST['search']);
		$list = $this->member->searchUsersBySupervisor($search, $_SESSION['supervisor']->supervisor_id);
		echo json_encode($list);
		exit();
	}
	
	public function trackuserv6($user_id) {
		if ($user_id == 0) return;
		
		$reqArray = array();
		$reqArray['userInfo'] = $this->member->getUserInfoById($user_id);
		
		// get user tasks
		$where = "user_id = ".$user_id;
		$tasks = $this->task->getAllTasksBySupervisor($where, $_SESSION['supervisor']->supervisor_id);
		$jsonTasks = json_encode($tasks);
		$reqArray['jsonTasks'] = $jsonTasks;
		
		// get user current task
		$where .= " and actual_start is not null and task_status not in ('COMPLETED','CANCELLED')";
		$curTasks = $this->task->getAllTasksBySupervisor($where, $_SESSION['supervisor']->supervisor_id);
		
		if (count($curTasks) == 0) {
			$reqArray['curTask'] = null;
		} else {
			$reqArray['curTask'] = $curTasks[0];
			$locHistoryForTask= $this->user->getUserLocationsforTask($user_id,$reqArray['curTask']->task_id);
			$reqArray['currlocHistory'] =$locHistoryForTask;
		}
		
		
		// find user's current location
		if ($reqArray['userInfo']->last_latitude!="") {
			$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$reqArray['userInfo']->last_latitude},{$reqArray['userInfo']->last_longitude}&key=POPULATE_API_KEY";
	        $apiResult = file_get_contents($url);
			$apiJson = json_decode($apiResult);
			$addr = $apiJson->results[0]->formatted_address;
			$reqArray['curAddr'] = $addr;
		} else {
			$reqArray['curAddr'] = "This user doesn't have trackable device!";
		}
            	
		// get user location history 
		$locHistory = $this->user->getUserLocations($user_id);
		//$locHistory = $this->user->getUserLocations(27);
		$reqArray['locHistory'] = $locHistory;
		
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/trackuserv6', $reqArray);
	}
	
	public function trackuser($user_id, $focus='', $action=''){
	    if ($this->session->userdata('supervisor') == null || $this->session->userdata('supervisor')->supervisor_id == '')
            redirect(site_url('supervisor/login'), 'refresh');
	    
	    if ($user_id == '')
	        redirect(site_url('supervisor/home'), 'refresh');
	    
	    if (!$this->user->checkPermissionForUserData($this->session->userdata('supervisor')->supervisor_id, $user_id))
	        redirect(site_url('supervisor/home'), 'refresh');
	    
	    //parameters
	    $page_data['userID'] = $user_id;
	    
	    //user info
	    $page_data['userInfo'] = $this->member->getUserInfoById($user_id);
	    $user_email = $page_data['userInfo']->email;
	    
	    //current location info
	    $curLocation['latitude'] = $page_data['userInfo']->last_latitude;
	    $curLocation['longitude'] = $page_data['userInfo']->last_longitude;
	    
	    if ($curLocation['latitude'] != "") {
	        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$curLocation['latitude']},{$curLocation['longitude']}&key=POPULATE_API_KEY";
	        $apiResult = file_get_contents($url);
	        $apiJson = json_decode($apiResult);
	        $addr = $apiJson->results[0]->formatted_address;
	        $curLocation['curAddr'] = $addr;
	    } else {
	        $curLocation['curAddr'] = "This user doesn't have trackable device!";
	    }
	    
	    $page_data['curLocation'] = $curLocation;
	    
	    //get focus task and prev task
	    if ($focus == ''){
	        $page_data['action'] = 'init';
	        
	        //get current task
	        $sql = "select task.*, '".$user_email."' as user_email from task where user_id = $user_id and actual_start is not null and task_status not in ('COMPLETED','CANCELLED') ";
	        $sql .= " order by task.task_id ASC limit 1";
	        $curTasks = $this->db->query($sql)->result_array();
	        
	        if (count($curTasks) == 0) {
	            $page_data['curTask'] = null;
	        } else {
	            $page_data['curTask'] = $curTasks[0];
	        }
	        
	        //set previous position as current location
	        $page_data['prev'] = $page_data['curLocation'];
	        
	    } else {
	        $page_data['action'] = $action;
	        
	        if ($action == 'prev'){
	            
	            $sql = "select task.*, '".$user_email."' as user_email from task where user_id = $user_id and task_id < $focus";
	            $sql .= " order by task.task_id DESC limit 2";
	            $curTasks = $this->db->query($sql)->result_array();
	            
	            if (count($curTasks) == 0) {
	                $page_data['curTask'] = null;
	                $page_data['prev'] = null;
	            } else {
	                $page_data['curTask'] = $curTasks[0];
	            }
	            
	            if (count($curTasks) == 2) {
	                $page_data['prev'] = $curTasks[1];
	            } else {
	                $page_data['prev'] = null;
	            }
	            	            
	        } elseif ($action == 'next') {
	            
	            $sql = "select task.*, '".$user_email."' as user_email from task where user_id = $user_id and task_id > $focus";
	            $sql .= " order by task.task_id ASC limit 2";
	            $curTasks = $this->db->query($sql)->result_array();
	             
	            if (count($curTasks) == 0) {
	                $page_data['curTask'] = null;
	                $page_data['prev'] = null;
	            } else {
	                $page_data['curTask'] = $curTasks[0];
	            }
	             
	            if (count($curTasks) == 2) {
	                $page_data['prev'] = $curTasks[1];
	            } else {
	                $page_data['prev'] = null;
	            }
	            
	        }
	    }
	    
	    //get user movement for current task
	    if ($page_data['curTask'] != null){
	        $taskStartTime = $page_data['curTask']['actual_Start'];
	        $taskEndTime = $page_data['curTask']['actual_end'];
	        
	        if ($taskStartTime != null && $taskStartTime != '') {
	           $page_data['movLocations'] = $this->user->getMovementForTask($user_id, $taskStartTime, $taskEndTime);
	        } else {
	            $page_data['movLocations'] = null;
	        }
	    }
	    	    
	    $this->load->view('supervisor/header');
	    $this->load->view('supervisor/trackuser', $page_data);
	}
	
	public function test($user_id) {
		$list = $this->user->getUserLocations($user_id);
		
		$prev_lat = 0; $prev_long = 0;
		//print_r($list);
		foreach ($list as $info) {
			if ($info->latitude == $prev_lat && $info->longitude == $prev_long) {
				$this->user->deleteUserHistory($info->history_id);
			} else {
				$prev_lat = $info->latitude;
				$prev_long = $info->longitude;
			}
		}
	}
	
	public function Users() {
		$reqArray = array();
		$reqArray['UserList'] = $this->member->searchUsersBySupervisor("", $_SESSION['supervisor']->supervisor_id);
		$jsonUsers = json_encode($this->member->searchUsersBySupervisor("", $_SESSION['supervisor']->supervisor_id));
		$reqArray['JsonUsers']=$jsonUsers;
		
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/users', $reqArray);
	}
	
	public function UserEdit($id) {
		if (!$this->checkLogin()) redirect(  'supervisor/login', 'refresh');
		$reqArray = array();
		$reqArray['MsgFailed'] = "";
		$reqArray['MsgSuccess'] = "";
		
		if (isset($_REQUEST['username'])) {
			// save user information
			$username = $this->db->escape_str($_REQUEST['username']);
			$email = $this->db->escape_str($_REQUEST['email']);
			$pwd = $this->db->escape_str($_REQUEST['pwd']);
			$homephone = $this->db->escape_str($_REQUEST['homephone']);
			$mobilephone = $this->db->escape_str($_REQUEST['mobilephone']);
			$userrole = $this->db->escape_str($_REQUEST['userrole']);
			$vehicletype = $this->db->escape_str($_REQUEST['vehicletype']);
			$vehicle_reg = $this->db->escape_str($_REQUEST['vehicle_reg']);
			
			
			if ($this->member->modifyUserInfoById($id, $username, $email, $pwd, $homephone, $mobilephone, $userrole, $vehicletype, $vehicle_reg)) {
				$reqArray['MsgSuccess'] = "Successfully modified the user information";
			} else {
				$reqArray['MsgFailed'] = "Failed to modify user information";
			}
		}
		
		$reqArray['userInfo'] = $this->member->getUserInfoById($id);
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/useredit', $reqArray);
	}
	
	public function UserAdd() {
		if (!$this->checkLogin()) redirect(  'supervisor/login', 'refresh');
		$reqArray = array();
		$reqArray['MsgFailed'] = "";
		$reqArray['MsgSuccess'] = "";
		
		if (isset($_REQUEST['username'])) {
			// save user information
			$username = $this->db->escape_str($_REQUEST['username']);
			$email = $this->db->escape_str($_REQUEST['email']);
			$pwd = $this->db->escape_str($_REQUEST['pwd']);
			$homephone = $this->db->escape_str($_REQUEST['homephone']);
			$mobilephone = $this->db->escape_str($_REQUEST['mobilephone']);
			$userrole = $this->db->escape_str($_REQUEST['userrole']);
			$vehicletype = $this->db->escape_str($_REQUEST['vehicletype']);
			$vehicle_reg = $this->db->escape_str($_REQUEST['vehicle_reg']);
			
			
			
			// get company information from supervisor
			$company_id = $_SESSION['supervisor']->company_id;
			
			if ($this->member->addUserInfo($_SESSION['supervisor']->supervisor_id, $company_id, $username, $email, $pwd, $homephone, $mobilephone, $userrole, $vehicletype, $vehicle_reg)) {
				$reqArray['MsgSuccess'] = "Successfully added the user information";
			} else {
				$reqArray['MsgFailed'] = "Failed to add user information";
			}
		}
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/useradd', $reqArray);
	}
	
	public function CheckDuplicateEmail() {
		$email = $this->db->escape_str($_REQUEST['email']);
		$isAvailable = true; $msg = "";
		if ($this->member->FindUserByEmail($email)> 0) {
			$isAvailable = false;
			$msg = "This email address already registered.";
		} else {
		}
		echo json_encode(array(
		    'valid' => $isAvailable,
			'message' => $msg,
		));
	}
	
	public function Tasks() {
		$reqArray = array();
		$reqArray['filter_status'] = "";
		$reqArray['filter_date'] = "";
		$where = "";
		if (isset($_REQUEST['filter_status']) || isset($_REQUEST['filter_date'])) {
			$where = "1 = 1 ";
			if ($_REQUEST['filter_status']!="") {
				$where .= " and task_Status = '{$_REQUEST['filter_status']}'";
				$reqArray['filter_status'] = $_REQUEST['filter_status'];
			}
			if ($_REQUEST['filter_date']!="") {
				$where .= " and DATE(scheduled_Start) >= (NOW() - Interval {$_REQUEST['filter_date']} day) ";
				$reqArray['filter_date'] = $_REQUEST['filter_date'];
			}
		}
		
		$reqArray['TaskList'] = $this->task->findTasks($where, $_SESSION['supervisor']->supervisor_id);
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/tasks', $reqArray);
	}
	
	public function TaskEdit($id) {
		if (!$this->checkLogin()) redirect(  'supervisor/login', 'refresh');
		$reqArray = array();
		$reqArray['MsgFailed'] = "";
		$reqArray['MsgSuccess'] = "";
		if (isset($_REQUEST['userid'])) {
			$userid = $this->db->escape_str($_REQUEST['userid']);
			$sosstatus = $this->db->escape_str($_REQUEST['sosstatus']);
			$TasktypeCategory = $this->db->escape_str($_REQUEST['TasktypeCategory']);
			$task_Status = $this->db->escape_str($_REQUEST['task_Status']);
			$scheduled_Start = $this->db->escape_str($_REQUEST['scheduled_Start']);
			$scheduled_End = $this->db->escape_str($_REQUEST['scheduled_End']);
			$contact_name = $this->db->escape_str($_REQUEST['contact_name']);
			$contact_address = $this->db->escape_str($_REQUEST['contact_address']);
			$contact_phone = $this->db->escape_str($_REQUEST['contact_phone']);
			$Region = $this->db->escape_str($_REQUEST['Region']);
			$postcode = $this->db->escape_str($_REQUEST['postcode']);
			$country = $this->db->escape_str($_REQUEST['country']);
			$notes = $this->db->escape_str($_REQUEST['notes']);
			$company_id = $_SESSION['supervisor']->company_id;
			
			// find lat and long using contact address
			// check contact 
            $contactInfo = $this->member->FindContact($company_id, $contact_name, $contact_address, $Region, $contact_phone);
            if ($contactInfo == NULL) {
            	// find lat and long information
            	$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$contact_address." ".$Region."&key=POPULATE_API_KEY";
            	$url = str_replace(" ", "+", $url);
            	$apiResult = file_get_contents($url);
            	$apiJson = json_decode($apiResult);
            	$lat = $apiJson->results[0]->geometry->location->lat;
            	$long = $apiJson->results[0]->geometry->location->lng;
            	//InsertContact($name, $address, $city, $phone, $lat, $long) {
            	$this->member->InsertContact($company_id, $contact_name, $contact_address, $Region, $contact_phone, $lat, $long);
            } else {
            	$lat = $contactInfo->contact_lat;
            	$long = $contactInfo->contact_lng;
            }
            
            $this->task->updateTaskInfo($id, $company_id, $userid, $sosstatus, $TasktypeCategory, $task_Status, $scheduled_Start, $scheduled_End, $contact_name, $contact_address, $contact_phone, $Region, $postcode, $country, $lat, $long, $notes);
            $reqArray['MsgSuccess'] = "Successfully modified the task information";
		}
		
		$reqArray['taskInfo'] = $this->task->getTaskInfoById($id);
		$reqArray['UserList'] = $this->member->searchUsersBySupervisor("", $_SESSION['supervisor']->supervisor_id);
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/taskedit', $reqArray);
	}
	
	public function TaskAdd() {
		if (!$this->checkLogin()) redirect(  'supervisor/login', 'refresh');
		$reqArray = array();
		$reqArray['MsgFailed'] = "";
		$reqArray['MsgSuccess'] = "";
		
		if (isset($_REQUEST['userid'])) {
			$userid = $this->db->escape_str($_REQUEST['userid']);
			$sosstatus = $this->db->escape_str($_REQUEST['sosstatus']);
			$TasktypeCategory = $this->db->escape_str($_REQUEST['TasktypeCategory']);
			$task_Status = $this->db->escape_str($_REQUEST['task_Status']);
			$scheduled_Start = $this->db->escape_str($_REQUEST['scheduled_Start']);
			$scheduled_End = $this->db->escape_str($_REQUEST['scheduled_End']);
			$contact_name = $this->db->escape_str($_REQUEST['contact_name']);
			$contact_address = $this->db->escape_str($_REQUEST['contact_address']);
			$contact_phone = $this->db->escape_str($_REQUEST['contact_phone']);
			$Region = $this->db->escape_str($_REQUEST['Region']);
			$postcode = $this->db->escape_str($_REQUEST['postcode']);
			$country = $this->db->escape_str($_REQUEST['country']);
			$notes = $this->db->escape_str($_REQUEST['notes']);
			$company_id = $_SESSION['supervisor']->company_id;
			
			// find lat and long using contact address
			// check contact 
            $contactInfo = $this->member->FindContact($company_id, $contact_name, $contact_address, $Region, $contact_phone);
            if ($contactInfo == NULL) {
            	// find lat and long information
            	$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$contact_address." ".$Region."&key=POPULATE_API_KEY";
            	$url = str_replace(" ", "+", $url);
            	$apiResult = file_get_contents($url);
            	$apiJson = json_decode($apiResult);
            	$lat = $apiJson->results[0]->geometry->location->lat;
            	$long = $apiJson->results[0]->geometry->location->lng;
            	//InsertContact($name, $address, $city, $phone, $lat, $long) {
            	$this->member->InsertContact($company_id, $contact_name, $contact_address, $Region, $contact_phone, $lat, $long);
            } else {
            	$lat = $contactInfo->contact_lat;
            	$long = $contactInfo->contact_lng;
            }
            
            $this->task->addTaskInfo($company_id, $userid, $sosstatus, $TasktypeCategory, $task_Status, $scheduled_Start, $scheduled_End,  $contact_name, $contact_address, $contact_phone, $Region, $postcode, $country, $lat, $long, $notes);
            $reqArray['MsgSuccess'] = "Successfully added a task information";
		}
		$reqArray['UserList'] = $this->member->searchUsersBySupervisor("", $_SESSION['supervisor']->supervisor_id);
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/taskadd', $reqArray);
	}
	
	public function sendSms() {
		$contact_id = $_REQUEST['contact_id'];
		$contactInfo = $this->member->getContactInfoById($contact_id);
		$text = urlencode($_REQUEST['msg']);
		
		$to = $contactInfo->contact_phone;
		$special_chars = array('+', '(', ')', ' ');
		$to = str_replace($special_chars, "", $to);
		$callurl = "http://www.tm4b.com/client/api/http.php?username=TM4BID25242&password=39650431&type=broadcast&to={$to}&from=Geoclarity&msg={$text}&data_type=plain";
		//echo $callurl;
		$ret = file_get_contents($callurl);
		echo $ret;
	}
	
	public function SosTasks() {
		if (!$this->checkLogin()) redirect(  'supervisor/login', 'refresh');
		$reqArray = array();
		
		$curTasks = $this->task->getCurrentSOSTasks($_SESSION['supervisor']->supervisor_id);
		$newCurTasks = array();
		foreach ($curTasks as $sosInfo) {
			$sosInfo->resList = $this->task->getSosResponseList($sosInfo->sosmain_id);
			$newCurTasks[] = $sosInfo;
		}
		$reqArray['curTasks'] = $newCurTasks;

		$pastTasks = $this->task->getPastSOSTasks($_SESSION['supervisor']->supervisor_id);
		$reqArray['pastTasks'] = $pastTasks;
		$this->load->view('supervisor/header');
		$this->load->view('supervisor/sostasks', $reqArray);
	} 
	
	public function CancelSosTask($sosmain_id) {
		
		$sosMainInfo = $this->task->getSosTaskInfoById($sosmain_id);
		if ($sosMainInfo == null) {
			
		} else {
			$this->task->cancelSosTask($sosmain_id, $sosMainInfo->task_id);
			// find original user info 
			$originalUserInfo = $this->member->getUserInfoById($sosMainInfo->original_user);
			
			// send push message that task is cancelled
			$userGcmIds = array($originalUserInfo->gcmid);
			
			$data = array(
				"msg" => "Your sos task scheduling is finished", 
				"sosmain_id" => $sosmain_id, 
				"type" => 3 //   
			);		
			$this->sendPush($data, $userGcmIds);
			
			redirect(  'supervisor/sostasks', 'refresh');
		}
	}
	public function SosAssignTask($sosmain_id) {
		$user_id = $_REQUEST['user_id'];
		$sosMainInfo = $this->task->getSosTaskInfoById($sosmain_id);
		
		$this->task->assisnSosTask($sosmain_id, $sosMainInfo->task_id, $user_id);
		
		// find original user info 
		$originalUserInfo = $this->member->getUserInfoById($sosMainInfo->original_user);
		// send push message that task is assigned
		$userGcmIds = array($originalUserInfo->gcmid);
		
		$data = array(
			"msg" => "Your sos task scheduling is finished", 
			"sosmain_id" => $sosmain_id, 
			"type" => 3 //   
		);		
		$this->sendPush($data, $userGcmIds);
		
		// find assigned user info
		$assignedUserInfo = $this->member->getUserInfoById($user_id);
		$userGcmIds = array($assignedUserInfo->gcmid);
		
		$data = array(
			"msg" => "You are assigned a new sos task", 
			"sosmain_id" => $sosmain_id, 
			"type" => 2 //   
		);
		$this->sendPush($data, $userGcmIds);
		
		redirect(  'supervisor/sostasks', 'refresh');
		
	}
	public function sendPush($data, $regid) {
		$apiKey = "POPULATE_API_KEY";
		
		// Replace with real client registration IDs 
		$registrationIDs = array( "POPULATE_REGISTRATION_ID");
		//$registrationIDs = array($regid);
		if ($regid=='') $regid = $registrationIDs;
		// Message to be sent
		$message = "x";
		
		// Set POST variables
		$url = 'https://android.googleapis.com/gcm/send';
		if ($data == '') {
		$data = array ( 
					"msg" => "test test", 
					"sosmain_id" => 11
		    	);
		}
		$fields = array(
		                'registration_ids'  => $regid,
		                'data'              => $data, 
		                );
		
		$headers = array( 
		                    'Authorization: key=' . $apiKey,
		                    'Content-Type: application/json'
		                );
		
		// Open connection
		$ch = curl_init();
		
		// Set the url, number of POST vars, POST data
		curl_setopt( $ch, CURLOPT_URL, $url );
		
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
		
		// Execute post
		$result = curl_exec($ch);
		// Close connection
		curl_close($ch);
	}
}