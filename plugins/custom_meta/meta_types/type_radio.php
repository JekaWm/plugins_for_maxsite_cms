<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * MaxSite CMS
 * (c) http://max-3000.com/
 */

  // ��� ��������� ���� ��������� ������ � ������� � �������� �����
  
  // ������� ����� ���� ��������
  $page_id = mso_segment(3);
  // ������� ��� ������ �������
  if (!isset($row['page_type'])) $row['page_type'] = '';
  if ($row['page_type'])
  {
     $CI = & get_instance();
	   $CI->db->select('page_id, page_slug, page_title');
	   $CI->db->where('page_date_publish <', date('Y-m-d H:i:s'));
	   $CI->db->where('page_status', 'publish');
	   $CI->db->where('page_id', $page_id);
	   $CI->db->where('page_type_name', $options['page_type']);
	   $CI->db->join('page_type', 'page_type.page_type_id = page.page_type_id');
	   $CI->db->from('page');
	   $CI->db->order_by('page_id', 'random');
	   $CI->db->limit($options['count']);
	
	   $query = $CI->db->get();
	
	   if ($query->num_rows() > 0)	// ������� �������� ������� ����
	   {	
//		   $pages = $query->result_array();
			$values = explode('#', $values); // ��� �������� ��������� #
			
			if ($values) // ���� ���-��
			{
				foreach( $values as $val ) 
				{
					if ($value == trim($val)) $checked = 'checked="checked"';
						else $checked = '';
						
					$f .= '<input type="radio" name="' . $name_f . '" value="' . trim($val) . '" ' 
							. $checked . '> ' . trim($val) . $delimer . NR;
				}
     }
   }  
?>