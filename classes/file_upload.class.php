<?php

// this class handles file upload via webforms
/*

Arguments :
	$fieldname	= name of the file input field
	$path		= path of the folder where to put the file
	$filename	= name of the final file
	$width		= if set, max width of image
	$height		= if set, max height of image 
*/

class file_upload {
	
	
	function __construct($fieldname,$path,$filename = '',$width='',$height='') {
		$this->fieldname	= $fieldname;
		$this->path			= $path;
		$this->filename		= $filename;
		if ($width)		$this->width = $width;
		if ($height)	$this->height = $height;
	}
	
	
	function receive() {
		
		if ($_FILES[$this->fieldname]['type'] && $_FILES[$this->fieldname]['name']) {
			switch ($_FILES[$this->fieldname]['type']) {
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$this->ext = "jpg";
					break;
				case "image/gif":
					$this->ext = "gif";
					break;
				case "image/png":
				case "image/x-png":
					$this->ext = "png";
					break;
				default:
					$fname = strrev($_FILES[$this->fieldname]['name']);
					list($fext,$fname) = explode('.',$fname,2);
					$this->ext = strtolower(strrev($fext));
					
					if (!$this->filename)
						$this->filename = strrev($fname);
					
					if (in_array($fext,array('php','php3','php4','php5')))
						stop("Les fichiers de type <strong>$this->ext</strong> ne sont pas autorisés au téléchargement","Type de fichier interdit");
					
					// stop(sprintf('Mauvais type de fichier : <strong>%s</strong> (%s)',$_FILES[$this->fieldname]['type'],$_FILES[$this->fieldname]['name']));
				break;
			}
		
//			if (in_array($this->ext,array('jpg','png','gif')))
//				$size = GetImageSize ($_FILES[$this->fieldname]['tmp_name']); 
	
			// we verify the filesize
	//		if (!$_FILES[$this->fieldname]['size'] stop('Cette image n\'a pas de taille !!!');
	
	//		if ($_FILES[$this->fieldname]['size'] > (80*1024)) stop(sprintf('Image trop lourde : <strong>%s ko</strong>',intval($_FILES[$this->fieldname]['size']/1024)));

			return true;
		}
		
		return false;
	}
	
	
	// c'est la fonction qui génère le fullname, cad le nom complet du fichier (avec le chemin)
	function set_fullname() {
		global $conf;
		$this->fullname = $this->path;
		
		if ($this->filename) {
			if (strpos($this->filename,'.'))
				$this->fullname .= $this->filename;
			else
				$this->fullname .= $this->filename.'.'.$this->ext;
		} else {
			$this->fullname .= $_FILES[$this->fieldname]['name'];
		}
	}
	
	
	
	// c'est la fonction qui enregistre le fichier sur le disque
	function save() {

		$this->set_fullname();
		
		if (!copy($_FILES[$this->fieldname]['tmp_name'],$this->fullname))
			stop('Merde, erreur dans la copie du fichier :-(',500);
		@chmod($this->fullname,0777);
		
		return true;
	}
	
	
	
	// fonction de "post production" une fois qu'on a uploadé le fichier
	function postprod() {
		
		// là on ne fait qu'un resize du fichier mais on peut facilement modifier le traitement en surclassant la classe
		if ($this->width or $this->height)
			$this->shrink($this->width,$this->height);
		
		// souvent là on met à jour une info dans la base de données
		// $db->execute('update users set photo="'.$this->ext.'" where id = '.$user['id']);
	}
	
	
	// fonction de resize
	function shrink($maxW,$maxH) {

		// we resize the image if bigger than 80x80
		$size = GetImageSize($this->fullname);   // params of image
		if (($size[0]>$maxW) || ($size[1]>$maxH)) {
			// we do the resizing
//			phpinfo();
			$im=imagecreatefromjpeg($this->fullname);       // path to your gallery
			if (chkgd2()) {
				$thumb_img = imagecreatetruecolor($maxW,$maxH);
				ImageCopyResampled($thumb_img, $im, 0, 0, 0, 0, $maxW, $maxH, $size[0], $size[1]);
			} else {
				$thumb_img = imagecreate($maxW, $maxH);
				ImageCopyResized($thumb_img, $im, 0, 0, 0, 0, $maxW, $maxH, $size[0], $size[1]);
			}
			ImageDestroy($im);					// free memory
			if ($this->ext == 'jpg') {
				ImageJPEG($thumb_img,$this->fullname,70);	// try to save image
			} else {
				// here, the resizing changes the image type to jpg
				unlink($this->fullname);
				$this->ext = 'jpg';
				$this->fullname = str_replace('.gif','.jpg',$this->fullname);
				$this->fullname = str_replace('.png','.jpg',$this->fullname);
				ImageJPEG($thumb_img,$this->fullname,70);	// try to save image
				@chmod($this->fullname,0777);
			}
			ImageDestroy($thumb_img);			// free memory
		}
		
	}
	
	// gestion de la suppression du fichier
	// en fait, cette fonction doit vraiment être surclassée
	function delete() {
		if ($_POST[$this->fieldname.'_del'])
			stop("Il faut supprimer le fichier");
	}
	
	
	// c'est la fonction qui fait tout
	function manage() {
		if ($this->receive())
			if ($this->save())
				$this->postprod();
		
		$this->delete();
	}
	
}


/* exemple de surclassement

// on surclasse la classe file_upload_manager :
class job_icon_file_upload_maneger extends file_upload_manager {
	
	function postprod() {
		// on shrink l'image si nécessaire
		$this->shrink(120,100);
		// on met à jour dans la base
		global $oo;
		$db->execute("-- - set icon of job {$oo['id']}
			update jobs
			set logo='$this->ext'
			where id = {$oo['id']}	");
	}
	
}



// logo				= name of the <input type=file/>
// /Users/stephane/Web/mediaclubjobs/www/job_logo/	= path to the directory where the uploaded file is to be saved
// 'job_'.$oo['id']	= name of the writen file
// 120				= maxwidth of image
// 100				= maxheight of image
$fup = new job_icon_file_upload_manager('logo','/Users/steph...job_logo/','job_'.$oo['id'],'120','100');
$fup->manage();

*/
