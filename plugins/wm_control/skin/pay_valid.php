<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
	if(($_POST['LMI_PREREQUEST'] == 1)
	and ($_POST['LMI_PAYEE_PURSE'] == 'Z887351216372')
	and ($_POST['LMI_PAYMENT_AMOUNT'] == 1)
	and ($_POST['LMI_PAYMENT_NO'] == "1")){
		$admin_email = mso_get_option('admin_email', 'general', false); // email ���� �������� �����������
		if ($admin_email){			$email_client = '';
			if (isset($_POST['email'])){				$email_client = $_POST['email'];
				$subject = '������ �� ����� kerzoll.org.ua';
				$text = '��������� ������������! �� �������� ������� ������� ������ �� ����� <a href="http://kerzoll.org.ua">kerzoll.org.ua</a> � ������� '.$_POST['LMI_PAYMENT_AMOUNT'].' WM. ����������! ��� ������� �� ������ ����� � ������ ���� ������ ��������� � �������� ������! ������� ������� �������� ���������� ��� ������� � ����� ��������� � ������ ����� �� ��������! ������� �� ������������� ������ �������.';
				mso_mail($email_client, $subject, $text);
			}
			$subject = '������ � ����� �������.';
			$text = '��������� �����! � ����� ������� ���� ����������� ����� ����� ������� ������� �� ������� '.$_POST['LMI_PAYEE_PURSE'].' � ����� '.$_POST['LMI_PAYMENT_AMOUNT'].' WM. ����� ������ ���������� - '.$email_client;
			mso_mail($admin_email, $subject, $text);
			echo "YES";
		}
	}else{
		echo "NO";
	}
?>