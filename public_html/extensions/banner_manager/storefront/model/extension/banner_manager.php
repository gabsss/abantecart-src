<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/
if (! defined ( 'DIR_CORE' )) {
        header ( 'Location: static_pages/' );
}

class ModelExtensionBannerManager extends Model {

	public function getBanner($banner_id, $language_id) {
		$banner_id = (int)$banner_id;
		$language_id = (int)$language_id;
		if(!$language_id){
			$language_id = (int)$this->session->data['content_language_id'];
		}
		// check is description presents
		$sql = "SELECT DISTINCT language_id
				FROM `" . DB_PREFIX . "banner_descriptions`
				WHERE banner_id='".$banner_id."'
				ORDER BY language_id ASC";
		$result = $this->db->query($sql);
		$counts = array();
		foreach($result->rows as $row){
			$counts[] = $row['language_id'];
		}
		if(!in_array($language_id,$counts)){
			$language_id = $counts[0];
		}

		$sql = "SELECT  *
				FROM `" . DB_PREFIX . "banners` b
				LEFT JOIN `" . DB_PREFIX . "banner_descriptions` bd ON bd.banner_id = b.banner_id AND bd.language_id = '".$language_id."'
				WHERE b.banner_id='".$banner_id."'";
		$result = $this->db->query($sql);
	return $result->row;
	}

	public function getBanners( $custom_block_id ) {
		$custom_block_id = (int)$custom_block_id;
		if(!$custom_block_id) return;

		if ( !empty($data['content_language_id']) ) {
			$language_id = ( int )$data['content_language_id'];
		} else {
			$language_id = (int)$this->config->get('storefront_language_id');
		}
		// get block info
		$block_info = $this->layout->getBlockDescriptions($custom_block_id);
		foreach($block_info[$language_id] as $k=>$v){
			$this->data[$k] = $v;
		}
		$content = $block_info[$language_id]['content'];
			if($content){
			$content = unserialize($content);
		}else{
			$content = current($block_info);
			$content = unserialize($content['content']);
		}
		$banner_group_name = $content['banner_group_name'];


		$sql = "SELECT *
				FROM " . DB_PREFIX . "banners b
				LEFT JOIN " . DB_PREFIX . "banner_descriptions bd ON (b.banner_id = bd.banner_id)
				WHERE bd.language_id = '" . $language_id . "'
					AND b.status='1'
					AND (NOW() BETWEEN `start_date` AND CASE WHEN `end_date`='0000-00-00 00:00:00' THEN  NOW() + INTERVAL 1 MONTH ELSE `end_date` END)
					AND (`banner_group_name` = '".$banner_group_name."'
						OR b.banner_id IN (SELECT DISTINCT id
		                            FROM ".DB_PREFIX."custom_lists
								    WHERE custom_block_id = '".$custom_block_id."' AND data_type='banner_id'))
				ORDER BY `banner_group_name` ASC, b.sort_order ASC";
		$result = $this->db->query($sql);
		return $result->rows;

	}

	public function writeBannerStat( $banner_id, $type = 1 ) {
		$banner_id = (int)$banner_id;
		$type = (int)$type;

		$user_info = array(
			'user_id' => (is_object($this->user) ? $this->user->getId() : ''),
			'user_ip' => $this->request->server['REMOTE_ADDR'],
			'user_host' => $this->request->server['REMOTE_HOST'],
			'user_host' => $this->request->server['REMOTE_HOST'],
			'rt' => $this->request->get['rt']
		);

		$sql = "INSERT INTO " . DB_PREFIX . "banner_stat (`banner_id`, `type`, `store_id`, `user_info`)
				VALUES ('".$banner_id."', '".$type."', '".$this->config->get('config_store_id')."', '".serialize($user_info)."')";
		$this->db->query( $sql );
		return true;
	}
}