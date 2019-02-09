<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * MaxSite CMS
 * (c) http://max-3000.com/
 */

  // ��� ���� ��������� ������ � ������� � �������� �����
  

  // ������� ��� ������ �������
  if (!isset($row['page_type'])) $row['page_type'] = '';
  if ($row['page_type'])
  {
     $CI = & get_instance();
	   $CI->db->select('page_id, page_slug, page_title , page_type_name ');
	   $CI->db->where('page_date_publish <', date('Y-m-d H:i:s'));
	   $CI->db->where('page_status', 'publish');
	   $CI->db->where('page_id', $page_id);
	   $CI->db->where('page_type_name', $row['page_type']);
	   $CI->db->join('page_type', 'page_type.page_type_id = page.page_type_id');
	   $CI->db->from('page');
	   $CI->db->order_by('page_id', 'random');
	
	   $query = $CI->db->get();
	
	   if ($query->num_rows() > 0)	// ������� �������� ������� ����
	   {	
	      $type_desc = '';
	      foreach ($query->result_array() as $r) 
	        if (isset($r['page_type_name'])) $type_desc = $r['page_type_name'];
  
      
		   	// �������� ������ ���� ������� �� ������� ������������� ����
			  $source_type = $row['source_type'];
			  // ����� ���� ����� �����������
			  $result_field = $row['result_field'];
			  // ����� ���� ����� ����������
			  $lookup_field = $row['lookup_field'];
			
			  // �������� ������ ���� ������� ����� ��� ���� ������ ������� ����
			
       $CI = & get_instance();
       $CI->db->select($result_field . ',' . $lookup_field);
	     $CI->db->where('page_date_publish <', date('Y-m-d H:i:s'));
	     $CI->db->where('page_status', 'publish');
	     $CI->db->where('page_type_name', $source_type);
	     $CI->db->join('page_type', 'page_type.page_type_id = page.page_type_id');
	     $CI->db->from('page');
	     $CI->db->order_by('page_id', 'random');
	
	     $query = $CI->db->get();
	     $lookup_count = $query->num_rows();
//			 $f .= '<p>' . t('�������� ���� ', 'admin') . $type_desc . ' (' . $lookup_count . ')</p>';
			 
	     if ($lookup_count > 0)	// ���� ��������
	     {
				  $f .= '<select name="' . $name_f . '">';	     
	        foreach ($query->result_array() as $r)
	        {
					   $val = $r[$result_field];
					   $val_t = $r[$lookup_field];
					   if ($value == $val) $checked = 'selected="selected"';
						   else $checked = '';
						
					   $f .= NR . '<option value="' . $val . '" ' . $checked . '>' . $val_t . '</option>';	      
	         }
	       $f .= NR . '</select>' . NR;
	     }
	     else
	     {
	       //���� ������� ��� ������ ���, ����� ������� ��� ��������� ����
	       $value = str_replace('_QUOT_', '&quot;', $value);
         $f .= '<input type="text" name="' . $name_f . '" value="' . $value . '">' . NR;
	     }	
     }
   }  
?>