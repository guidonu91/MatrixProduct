<?php
	require_once('../tcpdf/tcpdf.php');
	error_reporting(0);
	
	/*Para poder pasar parámetros a la url utilizamos la directiva register_globals de php(que se encuentra en el archivo php.ini), dado que desde la versión PHP 5.4.9 esta directiva quedó obsoleta utilizamos el siguiente "if" para emular el comportamiento de la directiva register_globals con valor "on"*/
	if (!ini_get('register_globals')) {
		$superglobals = array($_SERVER, $_ENV,
			$_FILES, $_COOKIE, $_POST, $_GET);
		if (isset($_SESSION)) {
			array_unshift($superglobals, $_SESSION);
		}
		foreach ($superglobals as $superglobal) {
			extract($superglobal, EXTR_SKIP);
		}
	}
	
	/*Extendemos la clase TCPDF para agregar encabezado y pie de página al documento pdf*/
	class MIPDF extends TCPDF {
		//Encabezado
		public function Header() {
			$imagen = 'logo_uc.png';
			$this->Image($imagen, PDF_MARGIN_LEFT, 10, 31, 12, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
			$this->SetFont('helvetica', '', 15);
			$this->SetXY(PDF_MARGIN_LEFT+15,16);
			$this->Cell(0, 40, 'Trabajo Práctico de Lenguajes de Programación 3', 0, false, 'C', 0, '', 0, false, 'M', 'M');
		}
		//Pie de página
		public function Footer() {
			$this->SetY(-15);
			$this->SetFont('times', 'I', 8);
			$this->Cell(0, 10, 'Página '.$this->getAliasNumPage(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
		}
	}
	
	/*cargar_matriz: crea una matriz a partir de los valores obtenidos en el archivo m1.txt o m2.txt. Crea una matriz llena de ceros, lee cada línea del archivo, va obtieniendo los valores de fila, columna y valor de celda y cargando la matriz a partir de estos valores. Retorna la matriz con todos los valores cargados(los especificados en el archivo y los ceros)*/
	function cargar_matriz($m, $nro_filas, $nro_columnas){
		$matriz = array_fill(1, $nro_filas, array_fill(1, $nro_columnas, 0));
		while (($bufer = fgets($m)) !== false) {
			$valores = explode(',',$bufer);
			$fila = trim($valores[0]);
			$columna = trim($valores[1]);
			$valor = trim($valores[2]);
			$matriz[$fila][$columna] = $valor;
		}
		return $matriz;
	}
	
	/*producto_de_matrices: recibe las matrices 1 y 2 y crea la matriz producto resultante. Crea una matriz llena de ceros con número de filas igual al número de filas de la matriz1 y el número de columnas igual al número de columnas de la matriz2, luego se calcula el valor de cada celda de la matriz mediante las reglas del producto de matrices de algebra lineal. Retorna la matriz con los valores obtenidos del producto de matrices*/
	function producto_de_matrices($matriz1, $matriz2, $nro_filas, $nro_columnas, $n){
		$matriz = array_fill(1, $nro_filas, array_fill(1, $nro_columnas, 0));
		for ($i=1;$i<=$nro_filas;$i++){
			for ($j=1;$j<=$nro_columnas;$j++){
				$k = 1;
				while ($k<=$n){
					$matriz[$i][$j] = $matriz[$i][$j] + ($matriz1[$i][$k] * $matriz2[$k][$j]);
					$k++;
				}
			}
		}
		return $matriz;
	}
	
	/*nro_filas_mayor: funcion auxilar que recibe el número de filas de las matrices 1 y 2 y retorna el mayor*/
	function nro_filas_mayor($f1,$f2){
		if ($f1>$f2)
			return $f1;
		else
			return $f2;
	}
	
	/*imprimir_matriz1: coloca en el pdf la matriz1 y a continuación el signo "x" del producto. Si la bandera que se activa al hacer click sobre una celda de la matriz resultante se pone en "1", entonces se rellenan con un color amarillo las celdas en la matriz1 que se encuentran en la misma fila que la celda clickeada en la matriz resultante*/
	function imprimir_matriz1($pdf, $matriz1, $f1, $c1, $ba = 0, $x = 0){
		$pdf->SetAbsXY(PDF_MARGIN_LEFT,PDF_MARGIN_TOP+13);
		for ($i=1;$i<=$f1;$i++){
			for ($j=1;$j<=$c1;$j++){
				if ($j == $c1){
					if ($ba==1 and $x==$i)
						$pdf->Cell(8, 5, $matriz1[$i][$j], 1, 1, 'C', 1, '', 0);
					else
						$pdf->Cell(8, 5, $matriz1[$i][$j], 1, 1, 'C', 0, '', 0);
				}
				else{
					if ($ba==1 and $x==$i )
						$pdf->Cell(8, 5, $matriz1[$i][$j], 1, 0, 'C', 1, '', 0);
					else
						$pdf->Cell(8, 5, $matriz1[$i][$j], 1, 0, 'C', 0, '', 0);
				}
			}
		}
		$pdf->SetAbsXY(PDF_MARGIN_LEFT+8*$c1+1,PDF_MARGIN_TOP+13+(($f1-1)*5.5)/2);
		$pdf->Write(0,'x');
	}
	
	/*imprimir_matriz2: coloca en el pdf la matriz2 y a continuación el signo "=". Si la bandera que se activa al hacer click sobre una celda de la matriz resultante se pone en "1", entonces se rellenan con un color amarillo las celdas en la matriz2 que se encuentran en la misma columna que la celda clickeada en la matriz resultante*/
	function imprimir_matriz2($pdf, $matriz2, $f2, $c2, $fr, $ba = 0, $y = 0){
		$pdf->SetAbsXY(PDF_MARGIN_LEFT+$f2*8+6,PDF_MARGIN_TOP+13);
		for ($i=1;$i<=$f2;$i++){
			for ($j=1;$j<=$c2;$j++){
				if ($j == $c2){
					if ($ba==1 and $y==$j)
						$pdf->Cell(8, 0, $matriz2[$i][$j], 1, 1, 'C', 1, '', 0);
					else
						$pdf->Cell(8, 0, $matriz2[$i][$j], 1, 1, 'C', 0, '', 0);
					$pdf->SetAbsX(PDF_MARGIN_LEFT+$f2*8+6);
				} else
					if ($ba==1 and $y==$j)
						$pdf->Cell(8, 0, $matriz2[$i][$j], 1, 0, 'C', 1, '', 0);
					else
						$pdf->Cell(8, 0, $matriz2[$i][$j], 1, 0, 'C', 0, '', 0);
			}
		}
		$pdf->SetAbsXY(PDF_MARGIN_LEFT+8*($f2+$c2)+6+1,PDF_MARGIN_TOP+13+(($fr-1)*5.5)/2);
		$pdf->Write(0,'=');
	}
	
	/*imprimir_matriz_resultado: coloca en el pdf la matriz resultado y coloca un vínculo a cada celda de la matriz. Este vínculo direcciona cada celda al mismo url en que nos encontramos pero con parámetros según la celda que fue clickeada. Los parametros que se pasan al url destino(el url en el que nos encontramos) son la fila y columna de la celda clickeada y una bandera que indica que se hizo click sobre una celda. Si se accede a esta función con la bandera activada entonces se rellena con un color amarillo la celda desde la que se clickeó*/
	function imprimir_matriz_resultado($pdf, $matriz_resultado, $fr, $cr, $c1, $ba=0, $x=0, $y=0){
		$pdf->SetAbsXY(PDF_MARGIN_LEFT+12+8*($c1+$cr),PDF_MARGIN_TOP+13);
		for ($i=1;$i<=$fr;$i++){
			for ($j=1;$j<=$cr;$j++){
				if ($j == $cr){
					if ($ba==1 and $x==$i and $y==$j)
						$pdf->Cell(8, 0, $matriz_resultado[$i][$j], 1, 1, 'C', 1, "http://localhost/TP2013/index.php?x=$i&y=$j&ba=1", 0);
					else
						$pdf->Cell(8, 0, $matriz_resultado[$i][$j], 1, 1, 'C', 0, "http://localhost/TP2013/index.php?x=$i&y=$j&ba=1", 0);
					$pdf->SetAbsX(PDF_MARGIN_LEFT+12+8*($c1+$cr));
				} else{
					if ($ba==1 and $x==$i and $y==$j)
						$pdf->Cell(8, 0, $matriz_resultado[$i][$j], 1, 0, 'C', 1, "http://localhost/TP2013/index.php?x=$i&y=$j&ba=1", 0);
					else
						$pdf->Cell(8, 0, $matriz_resultado[$i][$j], 1, 0, 'C', 0, "http://localhost/TP2013/index.php?x=$i&y=$j&ba=1", 0);
				}			
			}
		}
	}

	/*imprimir_operaciones: se ejecuta al hacer click sobre una celda de la matriz resultante. Coloca en el pdf las operaciones calculadas para obtener la celda clickeada en la matriz resultante. En primer lugar coloca el texto "Operaciones", luego calcula y coloca los productos de elementos de la fila de matriz1 y columna de la matriz2 necesarios y por último calcula y coloca la suma de estos productos en el pdf*/
	function imprimir_operaciones($pdf, $matriz1, $matriz2, $matriz_resultado, $n, $f_mayor, $ba=0, $x=0, $y=0){
		if ($ba == 1){
			$pdf->SetAbsXY(PDF_MARGIN_LEFT,PDF_MARGIN_TOP+$f_mayor*5.5+21);
			$pdf->Write(0,'Operaciones:');
			$pdf->Ln(9);
			$resultados = array();
			//productos
			for ($k=1;$k<=$n;$k++){
				array_push($resultados,$matriz1[$x][$k]*$matriz2[$k][$y]);
				$txt = $matriz1[$x][$k].' * '.$matriz2[$k][$y].' = '.$resultados[$k-1];
				$pdf->Write(0,$txt);
				$pdf->Ln(6);
			}
			$pdf->Ln(5);
			//suma de productos
			$result_final = 0;
			for ($k=0;$k<$n;$k++){
				$pdf->Write(0,$resultados[$k]);
				if ($k!=($n-1)) $pdf->Write(0,' + ');
				$result_final = $result_final + $resultados[$k];
			}
			$pdf->Write(0,' = '.$result_final);
		}
	}	
	
	/*Abrimos los archivos m1.txt y m2.txt*/
	$m1 = fopen("../TP2013/m1.txt", "r");
	$m2 = fopen("../TP2013/m2.txt", "r");
	
	//Vericamos si se abrieron los archivos con éxito
	if ($m1 and $m2) {
	
		//Obtenemos las dimensiones(número de filas y columnas) de la matriz 1
		$dim1 = fgets($m1);
		$valores = explode(',',$dim1);
		$f1 = trim($valores[0]);
		$c1 = trim($valores[1]);
		//Obtenemos las dimensiones(número de filas y columnas) de la matriz 2
		$dim2 = fgets($m2);
		$valores = explode(',',$dim2);
		$f2 = trim($valores[0]);
		$c2 = trim($valores[1]);
	
		//Verificamos si el número de columnas de la matriz1 es igual al número de filas de la matriz2 (propiedad del producto de matrices)
		if ($c1==$f2){
			
			//Creamos las matrices 1 y 2
			$matriz1 = cargar_matriz($m1, $f1, $c1);
			$matriz2 = cargar_matriz($m2, $f2, $c2);
			//Creamos la matriz resultado
			$fr = $f1;
			$cr = $c2;
			$n = $c1;
			$matriz_resultado = producto_de_matrices($matriz1, $matriz2, $fr, $cr, $n);
			
			//Creamos el objeto TCPDF
			$pdf = new MIPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);				
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->SetFillColor(255, 255, 127);
			$pdf->addPage();
			
			//Colocamos un texto inicial, matriz1, matriz2, matriz resultado y operaciones(si se clickeó alguna celda) en el pdf
			$pdf->Ln(4);
			$pdf->Write(0,'Haga click sobre la celda de la matriz resultante de la cual desea conocer el cálculo:');
			imprimir_matriz1($pdf, $matriz1, $f1, $c1, $ba, $x);
			imprimir_matriz2($pdf, $matriz2, $f2, $c2, $fr, $ba, $y);
			imprimir_matriz_resultado($pdf, $matriz_resultado, $fr, $cr, $c1, $ba, $x, $y);
			$f_mayor = nro_filas_mayor($f1,$f2);
			imprimir_operaciones($pdf, $matriz1, $matriz2, $matriz_resultado, $n, $f_mayor, $ba, $x, $y);
			
			//Mostramos el pdf
			$pdf->Output('TP-LP3-2013.pdf', 'I');
	
		}
		else{
			//En caso de que las dimensiones de las matrices posean un valor inválido mostramos un mensaje de error en el navegador
			echo "Error: Dimensiones de matrices no validas";
		}

	} else {
		//En caso de que no se pudieran abrir los archivos mostramos un mensaje de error en el navegador
		echo "Error: No se puede abrir el archivo";
	}
?>