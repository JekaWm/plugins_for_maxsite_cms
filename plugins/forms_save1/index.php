<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * MaxSite CMS
 * (c) http://max-3000.com/
 * Дополнения: Н. Громов (http://nicothin.ru/)
 */


# функция выполняется при активации (вкл) плагина
function forms_activate($args = array())
{	
	$CI = & get_instance();	

	if ( !$CI->db->table_exists('forms')) // нет таблицы forms
	{
		$charset = $CI->db->char_set ? $CI->db->char_set : 'utf8';
		$collate = $CI->db->dbcollat ? $CI->db->dbcollat : 'utf8_general_ci';
		$charset_collate = ' DEFAULT CHARACTER SET ' . $charset . ' COLLATE ' . $collate;
		
		$sql = "
		CREATE TABLE " . $CI->db->dbprefix . "forms (
		form_id bigint(20) NOT NULL auto_increment,
		form_ip varchar(255) NOT NULL default '',
		form_browser varchar(255) NOT NULL default '',
		form_date datetime default NULL,
		form_name varchar(255) NOT NULL default '',
		form_email varchar(255) NOT NULL default '',
		form_text longtext,
		form_refer varchar(255) NOT NULL default '',
		form_json_data longtext,
		PRIMARY KEY (form_id)
		)" . $charset_collate;
		
		$CI->db->query($sql);
	}
		
	return $args;
}

# функция выполняется при деинстяляции плагина
/*
function forms_uninstall($args = array())
{	
	//mso_delete_option('plugin_forms', 'plugins' ); // удалим созданные опции
	//mso_remove_allow('forms_edit'); // удалим созданные разрешения
	//mso_delete_option_mask('form_widget_', 'plugins' );
	
	// удалим таблицу
	$CI = &get_instance();
	$CI->load->dbforge();
	$CI->dbforge->drop_table('forms');

	return $args;
}
*/




# функция автоподключения плагина
function forms_autoload($args = array())
{
	mso_hook_add( 'content', 'forms_content'); # хук на вывод контента
}

# 
function forms_content_callback($matches) 
{
	$text = $matches[1];
	
	$text = str_replace("\r", "", $text);
	
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace("\t", ' ', $text);
	$text = str_replace('<br />', "<br>", $text);
	$text = str_replace('<br>', "\n", $text);
	$text = str_replace("\n\n", "\n", $text);
	$text = str_replace('     ', ' ', $text);
	$text = str_replace('    ', ' ', $text);
	$text = str_replace('   ', ' ', $text);
	$text = str_replace('  ', ' ', $text);
	$text = str_replace("\n ", "\n", $text);
	$text = str_replace("\n\n", "\n", $text);
	$text = trim($text);
	
	$out = ''; // убиваем исходный текст формы
	
	// на какой email отправляем
	$r = preg_match_all('!\[email=(.*?)\]!is', $text, $all);
	if ($r)
		$email = trim(implode(' ', $all[1]));
	else
		$email = mso_get_option('admin_email', 'general', 'admin@site.com');
	
	// тема письма
	$r = preg_match_all('!\[subject=(.*?)\]!is', $text, $all);
	if ($r)
		$subject = trim(implode(' ', $all[1]));
	else
		$subject = tf('Обратная связь');
	
	// имя, как оно будет показано в форме
	$r = preg_match_all('!\[name_title=(.*?)\]!is', $text, $all);
	if ($r)
		$name_title = trim(implode(' ', $all[1]));
	else
		$name_title = tf('Ваше имя');
	
	
	// email, как он будет показан в форме
	$r = preg_match_all('!\[email_title=(.*?)\]!is', $text, $all);
	if ($r)
		$email_title = trim(implode(' ', $all[1]));
	else
		$email_title = tf('Ваш email');
	
	
	// куда редиректить после отправки
	$r = preg_match_all('!\[redirect=(.*?)\]!is', $text, $all);
	if ($r)
		$redirect = trim(implode(' ', $all[1]));
	else
		$redirect = '';
	
	
	// ушка к форме
	$r = preg_match_all('!\[ushka=(.*?)\]!is', $text, $all);
	if ($r)
		$ushka = trim(implode(' ', $all[1]));
	else
		$ushka = '';
	
	// отправить копию на ваш email
	$r = preg_match_all('!\[nocopy\]!is', $text, $all);
	if ($r)
		$forms_subscribe = false;
	else
		$forms_subscribe = true;
	
	// кнопка Сброс формы
	$r = preg_match_all('!\[noreset\]!is', $text, $all);
	if ($r)
		$reset = false;
	else
		$reset = true;	
	
	
	// поля формы
	$r = preg_match_all('!\[field\](.*?)\[\/field\]!is', $text, $all);
	
	$f = array(); // массив для полей
	if ($r)
	{
		$fields = $all[1];

		
		if ($subject)
		{
			// поле тема письма делаем в виде обязательнного поля select.
			
			// формируем массив для формы
			$subject_f['require'] = 1;
			
			$subject_f['type'] = (mb_strpos($subject, '#') === false ) ? 'text' : 'select';
			
			// если это одиночное поле, но при этом текст сабжа начинается
			// с _ то ставим тип hidden
			if ($subject_f['type'] == 'text' and mb_strpos($subject, '_') === 0 ) 
			{
				$subject = mb_substr($subject . ' ', 1, -1, 'UTF-8');
				$subject_f['type'] = 'hidden'; 
			}
			
			$subject_f['description'] = tf('Тема письма');
			//$subject_f['tip'] = t('Выберите тему письма');
			$subject_f['values'] = $subject;
			$subject_f['value'] = $subject;
			$subject_f['default'] = '';

			// преобразования, чтобы сделать ключ для поля 
			$f1['subject'] = $subject_f; // у поля тема будет ключ subject
			foreach($f as $key=>$val) $f1[$key] = $val; 
			$f = $f1;
		}
		
		$i = 0;

		foreach ($fields as $val)
		{
			$val = trim($val);
			
			if (!$val) continue;
			
			$val = str_replace(' = ', '=', $val);
			$val = str_replace('= ', '=', $val);
			$val = str_replace(' =', '=', $val);
			$val = explode("\n", $val); // разделим на строки
			$ar_val = array();
			foreach ($val as $pole)
			{
				$pole = preg_replace('!=!', '_VAL_', $pole, 1);
				
				$ar_val = explode('_VAL_', $pole); // строки разделены = type = select
				if ( isset($ar_val[0]) and isset($ar_val[1]))
					$f[$i][$ar_val[0]] = $ar_val[1];
			}
			
			
			$i++;
		}
		
		if (!$f) return ''; // нет полей - выходим
		
		// теперь по-идее у нас есть вся необходимая информация по полям и по форме
		// смотрим есть ли POST. Если есть, то проверяем введенные поля и если они корректные, 
		// то выполняем отправку почты, выводим сообщение и редиректимся
		
		// если POST нет, то выводим обычную форму
		
		if ($_POST) $_POST = mso_clean_post(array(
			'forms_antispam1' => 'integer',
			'forms_antispam2' => 'integer',
			'forms_antispam' => 'integer',
			'forms_name' => 'base',
			'forms_email' => 'email',
			'forms_session' => 'base',
			));
		
		if ( $post = mso_check_post(array('forms_session', 'forms_antispam1', 'forms_antispam2', 'forms_antispam',
					'forms_name', 'forms_email',  'forms_submit' )) )
		{
			mso_checkreferer();
			
			$out .= '<div class="forms-post">';
			// верный email?
			if (!$ok = mso_valid_email($post['forms_email']))
			{
				$out .= '<div class="message error small">' . tf('Неверный email!') . '</div>';
			}
			
			// антиспам 
			if ($ok)
			{
				$antispam1s = (int) $post['forms_antispam1'];
				$antispam2s = (int) $post['forms_antispam2'];
				$antispam3s = (int) $post['forms_antispam'];
				
				if ( ($antispam1s/984 + $antispam2s/765) != $antispam3s )
				{ // неверный код
					$ok = false;
					$out .= '<div class="message error small">' . tf('Неверная сумма антиспама') . '</div>';
				}
			}
			
			if ($ok) // проверим обязательные поля
			{
				foreach ($f as $key=>$val)
				{
					if ( $ok and isset($val['require']) and $val['require'] == 1 ) // поле отмечено как обязательное
					{
						if (!isset($post['forms_fields'][$key]) or !$post['forms_fields'][$key]) 
						{
							$ok = false;
							$out .= '<div class="message error small">' . tf('Заполните все необходимые поля!') . '</div>';
						}
					}
					if (!$ok) break;
				}
			}
			
			// всё ок
			if ($ok)
			{
				// формируем письмо и отправляем его
				
				if (!mso_valid_email($email)) 
					$email = mso_get_option('admin_email', 'general', 'admin@site.com'); // куда приходят письма
					
				$message = t('Имя: ') . $post['forms_name'] . "\n";
				$message .= t('Email: ') . $post['forms_email'] . "\n";
				

				$json_data = '{ "' . t('Имя') .'": "'. addcslashes($post['forms_name'], "\"\\" ) . '", ';
				$json_data .= '"' . t('Email') .'": "'.  addcslashes($post['forms_email'], "\"\\" ) . '", ';

				foreach ($post['forms_fields'] as $key=>$val)
				{
					if ($key === 'subject' and $val)
					{
						$subject = $val;
						continue;
					}
					
					$message .= $f[$key]['description'] . ': ' . $val . "\n\n";
					$json_data .= '"' . $f[$key]['description'].'": "'. addcslashes( $val, "\"\\" ) . '", ';
				}
				$json_data .= '}';

				$CI = &get_instance();
				// данные для новой записи
				$ins_data = array (
				    'form_date' => date('Y-m-d H:i:s'),
					'form_ip' => $_SERVER['REMOTE_ADDR'] ,
					'form_browser' => $_SERVER['HTTP_USER_AGENT'],
					'form_name' => $post['forms_name'] ,
					'form_email' => $post['forms_email'],
					'form_text' => $message,
					'form_refer' =>  $_SERVER['HTTP_REFERER'],
					'form_json_data' =>  $json_data,
				);
				// pr($ins_data);
				$res = ($CI->db->insert('forms', $ins_data)) ? '1' : '0';


				
				if ($_SERVER['REMOTE_ADDR'] and $_SERVER['HTTP_REFERER'] and $_SERVER['HTTP_USER_AGENT']) 
				{
					$message .= "\n" . tf('IP-адрес: ') . $_SERVER['REMOTE_ADDR'] . "\n";
					$message .= tf('Отправлено со страницы: ') . $_SERVER['HTTP_REFERER'] . "\n";
					$message .= tf('Браузер: ') . $_SERVER['HTTP_USER_AGENT'] . "\n";
				}
				
				// pr($message);
				
				$form_hide = mso_mail($email, $subject, $message, $post['forms_email']);
				
				if ( $forms_subscribe and isset($post['forms_subscribe']) ) 
					mso_mail($post['forms_email'], tf('Вами отправлено сообщение:') . ' ' . $subject, $message);
				
				
				$out .= '<div class="message ok small">' . tf('Ваше сообщение отправлено!') . '</div><p>' 
						. str_replace("\n", '<br>', htmlspecialchars($subject. "\n" . $message)) 
						. '</p>';
				
				if ($redirect) mso_redirect($redirect, true);

			}
			else // какая-то ошибка, опять отображаем форму
			{
				$out .= forms_show_form($f, $ushka, $forms_subscribe, $reset, $subject, $name_title, $email_title);
			}
			
			
			$out .= '</div>';
			
			$out .= mso_load_jquery('jquery.scrollto.js');
			$out .= '<script>$(document).ready(function(){$.scrollTo("div.forms-post", 500, {offset:-45});})</script>';

		}
		else // нет post
		{
			$out .= forms_show_form($f, $ushka, $forms_subscribe, $reset, $subject, $name_title, $email_title);
		}
	}

	return $out;
}

function forms_show_form($f = array(), $ushka = '', $forms_subscribe = true, $reset = true, $subject = '', $name_title = '', $email_title = '')
{
	$out = '';

	$antispam1 = rand(1, 10);
	$antispam2 = rand(1, 10);
	
	$id = 1; // счетчик для id label
	
	if ($subject)
	{
		// поле тема письма делаем в виде обязательнного поля select.
		
		// формируем массив для формы
		$subject_f['require'] = 1;
		
		// если в  subject есть #, то это несколько значений - select
		// если нет, значит обычное текстовое поле
		
		$subject_f['type'] = (mb_strpos($subject, '#') === false ) ? 'text' : 'select';
		
		// если это одиночное поле, но при этом текст сабжа начинается
		// с _ то ставим тип hidden
		if ($subject_f['type'] == 'text' and mb_strpos($subject, '_') === 0 ) 
		{
			$subject = mb_substr($subject . ' ', 1, -1, 'UTF-8');
			$subject_f['type'] = 'hidden'; 
		}
		
		$subject_f['description'] = tf('Тема письма');
		//$subject_f['tip'] = t('Выберите тему письма');
		$subject_f['values'] = $subject;
		$subject_f['value'] = $subject;
		$subject_f['default'] = '';
		
		// преобразования, чтобы сделать ключ для поля 
		$f1['subject'] = $subject_f; // у поля тема будет ключ subject
		
		foreach($f as $key=>$val) $f1[$key] = $val; 
		$f = $f1;
		
	}

	$out .= NR . '<div class="forms"><form method="post" class="plugin_forms fform">' . mso_form_session('forms_session');
	
	$out .= '<input type="hidden" name="forms_antispam1" value="' . $antispam1 * 984 . '">';
	$out .= '<input type="hidden" name="forms_antispam2" value="' . $antispam2 * 765 . '">';
	
	// для сохранения отправленных полей смотрим POST
	if (!isset($_POST['forms_name']) or !$pvalue = mso_clean_str($_POST['forms_name'])) $pvalue = '';
	
	// обязательные поля
	if ($name_title)
	{
		$out .= '<p><label class="ffirst ftitle" title="' . tf('Обязательное поле') . '" for="id-' . ++$id . '">' . $name_title . '*</label><span><input name="forms_name" type="text" value="' . $pvalue . '" placeholder="' . $name_title . '" required id="id-' . $id . '"></span></p>';
	}
	else 
	{
		$out .= '<p><label class="ffirst ftitle" title="' . tf('Обязательное поле') . '" for="id-' . ++$id . '">' . tf('Ваше имя*') . '</label><span><input name="forms_name" type="text" value="' . $pvalue . '" placeholder="' . tf('Ваше имя') . '" required id="id-' . $id . '"></span></p>';
	}

	
	if (!isset($_POST['forms_email']) or !$pvalue = mso_clean_str($_POST['forms_email'], 'base|email')) $pvalue = '';
	
	if ($email_title)
	{
		
		$out .= '<p><label class="ffirst ftitle" title="' . tf('Обязательное поле') . '" for="id-' . ++$id . '">' . $email_title . '*</label><span><input name="forms_email" type="email" value="' . $pvalue . '" placeholder="' . $email_title . '" required id="id-' . $id . '"></span></p>';
	}
	else 
	{
		$out .= '<p><label class="ffirst ftitle" title="' . tf('Обязательное поле') . '" for="id-' . ++$id . '">' . tf('Ваш email*') . '</label><span><input name="forms_email" type="email" value="' . $pvalue . '" placeholder="' . tf('Ваш email') . '" required id="id-' . $id . '"></span></p>';
	}
	
	
	// тут указанные поля в $f
	foreach ($f as $key=>$val)
	{
		if (!isset($val['type'])) continue;
		if (!isset($val['description'])) $val['description'] = '';
		
		$val['type'] = trim($val['type']);
		$val['description'] = trim($val['description']);
		
		if (isset($val['require']) and  trim($val['require']) == 1) 
		{
			$require = '*';
			$require_title = ' title="' . tf('Обязательное поле') . '"';
			$required = ' required';
		}		
		else 
		{
			$require = '';
			$require_title = '';
			$required = '';
		}
		
		if (isset($val['attr']) and  trim($val['attr'])) $attr = ' ' . trim($val['attr']);
			else $attr = '';
		
		if (isset($val['value']) and  trim($val['value'])) $pole_value = htmlspecialchars(tf(trim($val['value'])));
			else $pole_value = '';
		
		
		// изменим $pole_value значение, если оно было в _POST
		// для полей можно задать правила фильрации функции mso_clean_str
		if (isset($val['clean']) and trim($val['clean'])) $clean = trim($val['clean']);
			else $clean = 'base';
		
		if (isset($_POST['forms_fields'][$key]) and $pvalue = mso_clean_str($_POST['forms_fields'][$key], $clean)) 
			$pole_value = $pvalue;
		
		
		if (isset($val['placeholder']) and  trim($val['placeholder'])) $placeholder = ' placeholder="' . htmlspecialchars(tf(trim($val['placeholder']))) . '"';
			else $placeholder = '';	
			
		$description = t(trim($val['description']));
		
		if (isset($val['tip']) and trim($val['tip']) ) $tip = NR . '<p class="nop"><span class="ffirst"></span><span class="fhint">'. trim($val['tip']) . '</span></p>';
			else $tip = '';
			
		if ($val['type'] == 'text') #####
		{
			//type_text - type для input HTML5
			if (isset($val['type_text']) and  trim($val['type_text'])) $type_text = htmlspecialchars(trim($val['type_text']));
				else $type_text = 'text';
			
			$out .= NR . '<p><label class="ffirst ftitle" for="id-' . ++$id . '"' . $require_title . '>' . $description . $require . '</label><span><input name="forms_fields[' . $key . ']" type="' . $type_text . '" value="' . $pole_value . '" id="id-' . $id . '"' . $placeholder . $required . $attr . '></span></p>' . $tip;

		}
		elseif ($val['type'] == 'select') #####
		{
			if (!isset($val['default'])) continue;
			if (!isset($val['values'])) continue;
			
			$out .= NR . '<p><label class="ffirst ftitle" for="id-' . ++$id . '"' . $require_title . '>' . $description . $require . '</label><span><select name="forms_fields[' . $key . ']" id="id-' . $id . '"' . $attr . '>';
			
			$default = trim($val['default']);
			$values = explode('#', $val['values']);
			
			foreach ($values as $value)
			{
				$value = trim($value);
				
				if (!$value) continue; // пустые опции не выводим
				
				if ($pole_value and $value == $pole_value)
				{
					$checked = ' selected="selected"';
				}
				elseif ($value == $default and !$pole_value) 
				{
					$checked = ' selected="selected"';
				}
				else $checked = '';
				
				$out .= '<option' . $checked . '>' . htmlspecialchars(tf($value)) . '</option>';
			}
			
			$out .= '</select></span></p>' . $tip;

		}
		elseif ($val['type'] == 'textarea') #####
		{
			$out .= NR . '<p><label class="ffirst ftitle ftop" for="id-' . ++$id . '"' . $require_title . '>' . $description . $require . '</label><span><textarea name="forms_fields[' . $key . ']" id="id-' . $id . '"' . $placeholder . $required. $attr . '>' . $pole_value . '</textarea></span></p>' . $tip;
		
		}
		elseif ($val['type'] == 'hidden') #####
		{
			$out .= NR . '<input name="forms_fields[' . $key . ']" type="hidden" value="' . $pole_value . '" id="id-' . $id . '"' . $attr . '>';
		}
		
	}
	
	// обязательные поля антиспама и отправка и ресет
	$out .= NR . '<p class="forms_antispam"><label class="ffirst ftitle" for="id-' . ++$id . '">' . $antispam1 . ' + ' . $antispam2 . ' =</label>';
	$out .= '<span><input name="forms_antispam" type="number" required maxlength="3" value="" placeholder="' . tf('Укажите свой ответ') . '" id="id-' . $id . '"></span></p>';
	
	if ($forms_subscribe)
		$out .= NR . '<p><span class="ffirst"></span><label><input name="forms_subscribe" value="" type="checkbox"  class="forms_checkbox"> ' . tf('Отправить копию письма на ваш e-mail') . '</label></p>';
	
	$out .= NR . '<p><span class="ffirst"></span><span class="submit"><button name="forms_submit" type="submit" class="forms_submit">' . tf('Отправить') . '</button>';
	
	if ($reset) $out .= ' <button name="forms_clear" type="reset" class="forms_reset">' . tf('Очистить форму') . '</button>';
	
	$out .= '</span></p>';
	
	if (function_exists('ushka')) $out .= ushka($ushka);
	
	$out .= '</form></div>' . NR;
	
	return $out;
}

# функции плагина
function forms_content($text = '')
{
	if (strpos($text, '[form]') !== false) $text = preg_replace_callback('!\[form\](.*?)\[/form\]!is', 'forms_content_callback', $text );
	return $text;
}

# end file