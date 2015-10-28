<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class reportes_logistico extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');
		$this->load->library('security');
		$this->load->library('tank_auth');
		$this->lang->load('tank_auth');
		$this->load->model('bo/modelo_dashboard');
		$this->load->model('model_tipo_red');
		$this->load->model('model_servicio');
		$this->load->model('bo/modelo_reportes_logistico');
		$this->load->model('general');
		$this->load->model('modelo_cobros');
		$this->load->model('bo/modelo_cedi');
		$this->load->model('bo/model_inventario');
	}

	function index()
	{
		if (!$this->tank_auth->is_logged_in()) 
		{																		// logged in
			redirect('/auth');
		}

		$id=$this->tank_auth->get_user_id();
		
		if(!$this->general->isAValidUser($id,"logistica"))
		{
			redirect('/auth/logout');
		}

		$usuario=$this->general->get_username($id);
		
		$style=$this->modelo_dashboard->get_style(1);

		$this->template->set("usuario",$usuario);
		$this->template->set("style",$style);
		
		$this->template->set_theme('desktop');
        $this->template->set_layout('website/main');
        $this->template->set_partial('header', 'website/bo/header');
        $this->template->set_partial('footer', 'website/bo/footer');
		$this->template->build('website/bo/logistico2/reportes/reportes');
	}
	
	function reporte_inventario()
	{
		$id=$this->tank_auth->get_user_id();
	
		if (!$this->tank_auth->is_logged_in())
		{																		// logged in
			redirect('/auth');
		}
	
		$id=$this->tank_auth->get_user_id();
	
		if(!$this->general->isAValidUser($id,"logistica"))
		{
			redirect('/auth/logout');
		}
	
		$usuario=$this->general->get_username($id);
		
		$inventario = $this->modelo_reportes_logistico->inventario();
		
		echo
		"<table id='datatable_fixed_column1' class='table table-striped table-bordered table-hover' width='100%'>
				<thead id='tablacabeza'>
					<th>ID</th>
					<th>Almacen / CEDI</th>
					<th>Producto</th>
					<th>Cantidad</th>
					<th>Bloqueo</th>
				</thead>
				<tbody>";
		foreach ($inventario as $producto){
			echo "<tr>
					<td class='sorting_1'>".$producto->id_inventario."</td>
					<td>".$producto->almacen."</td>
					<td>".$producto->producto."</td>
					<td>".$producto->cantidad."</td>
					<td>".$producto->bloqueados."</td>
				</tr>";
		}
			
		echo "</tbody>
			</table><tr class='odd' role='row'>";
	
	
	}
	
	function reporte_inventario_excel()
	{
		if (!$this->tank_auth->is_logged_in())
		{																		// logged in
			redirect('/auth');
		}
	
		$id=$this->tank_auth->get_user_id();
	
		if(!$this->general->isAValidUser($id,"logistica"))
		{
			redirect('/auth/logout');
		}
	
		$usuario=$this->general->get_username($id);
		$inventario = $this->modelo_reportes_logistico->inventario();
		$i=0;
		$this->load->library('excel');
		$this->excel=PHPExcel_IOFactory::load(FCPATH."/application/third_party/templates/reporte_inventario.xls");
		foreach ($inventario as $producto)
		{ 
			$i=$i+1;
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(0, ($i+8), $producto->id_inventario);
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(1, ($i+8), $producto->almacen);
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(2, ($i+8), $producto->producto);
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(3, ($i+8), $producto->cantidad);
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(4, ($i+8), $producto->bloqueados);
		}
	
		$filename='inventario.xls'; //save our workbook as this file name
		header('Content-Type: application/vnd.ms-excel'); //mime type
		header('Content-Disposition: attachment;filename="'.$filename.'"'); //tell browser what's the file name
		header('Cache-Control: max-age=0'); //no cache
	
		//save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
		//if you want to save it as .XLSX Excel 2007 format
		$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
		//force user to download the Excel file without writing it to server's HD
		//$objWriter->save(getcwd()."/media/reportes/".$filename);
		$objWriter->save('php://output');
	}
	
	function reporte_entrada_excel(){
		if (!$this->tank_auth->is_logged_in())
		{																		// logged in
		redirect('/auth');
		}
		
		$id=$this->tank_auth->get_user_id();
		
		if(!$this->general->isAValidUser($id,"logistica"))
		{
			redirect('/auth/logout');
		}
		
		$usuario=$this->general->get_username($id);
		
		$inicio = '2000-01-01';
		if($_GET['inicio'] != null){
			$inicio = $_GET['inicio'];
		}
		$fin = '3000-12-12';
		if($_GET['fin'] != null){
			$fin = $_GET['fin'];
		}
		$Entradas=$this->model_inventario->historial_entradas($inicio,$fin,'E');
     	$Cedis=$this->model_inventario->getAlmacenesCedi();
   	    $Documento=$this->model_inventario->getAlldocumento();
   	    $Producto=$this->model_inventario->getProductos();
		
		$i=0;
		$this->load->library('excel');
		$this->excel=PHPExcel_IOFactory::load(FCPATH."/application/third_party/templates/reporte_entrada.xls");
	foreach ($Entradas as $entrada)
		{
			$i=$i+1;
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(0, ($i+8), $entrada->id_inventario_historial);
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(1, ($i+8), $entrada->fecha);
			foreach ($Cedis as $Cedi){
				if($Cedi->id_cedi==$entrada->id_origen){
						
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(2, ($i+8), $Cedi->nombre);
				}
					
			}
			if($entrada->id_origen=='0'){
				$this->excel->getActiveSheet()->setCellValueByColumnAndRow(2, ($i+8), $entrada->otro_origen);
	
			}
	
			foreach ($Cedis as $Cedi){
				if($Cedi->id_cedi==$entrada->id_destino){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(3, ($i+8), $Cedi->nombre);
				}
			}
			if($entrada->id_destino=='0'){
				$this->excel->getActiveSheet()->setCellValueByColumnAndRow(3, ($i+8), $entrada->otro_origen);
	
			}
			foreach ($Documento as $documento){
				if($documento->id_doc==$entrada->id_documento){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(4, ($i+8), $documento->nombre);
	
	
				}
			}
			foreach ($Producto as $producto){
				if($producto->id==$entrada->id_mercancia){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(5, ($i+8), $producto->nombre);
						
				}
			}
			
			
			

		}
		
		$filename='Entradas.xls'; //save our workbook as this file name
		header('Content-Type: application/vnd.ms-excel'); //mime type
		header('Content-Disposition: attachment;filename="'.$filename.'"'); //tell browser what's the file name
		header('Cache-Control: max-age=0'); //no cache
		
		//save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
		//if you want to save it as .XLSX Excel 2007 format
		$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
		//force user to download the Excel file without writing it to server's HD
		//$objWriter->save(getcwd()."/media/reportes/".$filename);
		$objWriter->save('php://output');
	}
	
function reporte_salida_excel(){
		if (!$this->tank_auth->is_logged_in())
		{																		// logged in
		redirect('/auth');
		}
		
		$id=$this->tank_auth->get_user_id();
		
		if(!$this->general->isAValidUser($id,"logistica"))
		{
			redirect('/auth/logout');
		}
		
		$usuario=$this->general->get_username($id);
		
		$inicio = '2000-01-01';
		if($_GET['inicio'] != null){
			$inicio = $_GET['inicio'];
		}
		$fin = '3000-12-12';
		if($_GET['fin'] != null){
			$fin = $_GET['fin'];
		}
		$Entradas=$this->model_inventario->historial_entradas($inicio,$fin,'S');
     	$Cedis=$this->model_inventario->getAlmacenesCedi();
   	    $Documento=$this->model_inventario->getAlldocumento();
   	    $Producto=$this->model_inventario->getProductos();
		
		$i=0;
		$this->load->library('excel');
		$this->excel=PHPExcel_IOFactory::load(FCPATH."/application/third_party/templates/reporte_salida.xls");
foreach ($Entradas as $entrada)
		{
			$i=$i+1;
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(0, ($i+8), $entrada->id_inventario_historial);
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(1, ($i+8), $entrada->fecha);
			foreach ($Cedis as $Cedi){
				if($Cedi->id_cedi==$entrada->id_origen){
						
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(2, ($i+8), $Cedi->nombre);
				}
					
			}
			if($entrada->id_origen=='0'){
				$this->excel->getActiveSheet()->setCellValueByColumnAndRow(2, ($i+8), $entrada->otro_origen);
	
			}
	
			foreach ($Cedis as $Cedi){
				if($Cedi->id_cedi==$entrada->id_destino){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(3, ($i+8), $Cedi->nombre);
				}
			}
			if($entrada->id_destino=='0'){
				$this->excel->getActiveSheet()->setCellValueByColumnAndRow(3, ($i+8), $entrada->otro_origen);
	
			}
			foreach ($Documento as $documento){
				if($documento->id_doc==$entrada->id_documento){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(4, ($i+8), $documento->nombre);
	
	
				}
			}
			foreach ($Producto as $producto){
				if($producto->id==$entrada->id_mercancia){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(5, ($i+8), $producto->nombre);
						
				}
			}
			
			
			

		}
		
		$filename='Salidas.xls'; //save our workbook as this file name
		header('Content-Type: application/vnd.ms-excel'); //mime type
		header('Content-Disposition: attachment;filename="'.$filename.'"'); //tell browser what's the file name
		header('Cache-Control: max-age=0'); //no cache
		
		//save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
		//if you want to save it as .XLSX Excel 2007 format
		$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
		//force user to download the Excel file without writing it to server's HD
		//$objWriter->save(getcwd()."/media/reportes/".$filename);
		$objWriter->save('php://output');
	}
	
	function reporte_entrada_salida_excel(){
		if (!$this->tank_auth->is_logged_in())
		{																		// logged in
			redirect('/auth');
		}
	
		$id=$this->tank_auth->get_user_id();
	
		if(!$this->general->isAValidUser($id,"logistica"))
		{
			redirect('/auth/logout');
		}
	
		$usuario=$this->general->get_username($id);
	
		$inicio = '2000-01-01';
		if($_GET['inicio'] != null){
			$inicio = $_GET['inicio'];
		}
		$fin = '3000-12-12';
		if($_GET['fin'] != null){
			$fin = $_GET['fin'];
		}
		$Entradas=$this->model_inventario->historial_entradas_salida($inicio,$fin);
		$Cedis=$this->model_inventario->getAlmacenesCedi();
		$Documento=$this->model_inventario->getAlldocumento();
		$Producto=$this->model_inventario->getProductos();
	
		$i=0;
		$this->load->library('excel');
		$this->excel=PHPExcel_IOFactory::load(FCPATH."/application/third_party/templates/reporte_entrada_salida.xls");
		foreach ($Entradas as $entrada)
		{
			$i=$i+1;
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(0, ($i+8), $entrada->id_inventario_historial);
			$this->excel->getActiveSheet()->setCellValueByColumnAndRow(1, ($i+8), $entrada->fecha);
			foreach ($Cedis as $Cedi){
				if($Cedi->id_cedi==$entrada->id_origen){
						
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(2, ($i+8), $Cedi->nombre);
				}
					
			}
			if($entrada->id_origen=='0'){
				$this->excel->getActiveSheet()->setCellValueByColumnAndRow(2, ($i+8), $entrada->otro_origen);
	
			}
	
			foreach ($Cedis as $Cedi){
				if($Cedi->id_cedi==$entrada->id_destino){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(3, ($i+8), $Cedi->nombre);
				}
			}
			if($entrada->id_destino=='0'){
				$this->excel->getActiveSheet()->setCellValueByColumnAndRow(3, ($i+8), $entrada->otro_origen);
	
			}
			foreach ($Documento as $documento){
				if($documento->id_doc==$entrada->id_documento){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(4, ($i+8), $documento->nombre);
	
	
				}
			}
			foreach ($Producto as $producto){
				if($producto->id==$entrada->id_mercancia){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(5, ($i+8), $producto->nombre);
						
				}
			}
			if ($entrada->tipo=='S'){
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(6, ($i+8), 'Salida');
				
			}else{
					$this->excel->getActiveSheet()->setCellValueByColumnAndRow(6, ($i+8),'Entrada');
				
			}	
				
				
	
		}
	
		$filename='Entradas_Salidas.xls'; //save our workbook as this file name
		header('Content-Type: application/vnd.ms-excel'); //mime type
		header('Content-Disposition: attachment;filename="'.$filename.'"'); //tell browser what's the file name
		header('Cache-Control: max-age=0'); //no cache
	
		//save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
		//if you want to save it as .XLSX Excel 2007 format
		$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
		//force user to download the Excel file without writing it to server's HD
		//$objWriter->save(getcwd()."/media/reportes/".$filename);
		$objWriter->save('php://output');
	}
}