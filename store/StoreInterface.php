<?php
interface StoreInterface{
	public function open();
	public function set($id,$data,$lifetime=0);
	public function get($id);
	public function delete($id);
	public function close();
}
?>