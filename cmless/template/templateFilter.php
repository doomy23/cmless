<?php 

abstract class TemplateFilter{
	const filter = null;
	
	/**
	 * Function used to filter the value
	 * @param unknown $value
	 * @param array $params
	 * @return unknown
	 */
	public abstract function filter($value, array $params);
	
}

class DatetimeFormatFilter extends TemplateFilter{
	const filter = "datetimeFormat";

	/**
	 * Format a datetime object or string into a specific format
	 * @param unknown $value
	 * @param array $params
	 * @return string 
	 */
	public function filter($value, array $params)
	{
		if(count($params)!=1)
			throw new TemplateException(sprintf("Filter datetimeFormat expected format parameter"));
		
		if(is_object($value) && get_class($value) == "DateTime"):
			$value = $value->format($params[0]);
		else:
			$value = (new DateTime($value))->format($params[0]);
		endif;

		return $value;
	}
}

class ResizeImageFilter extends TemplateFilter{
	const filter = "resizeImage";
	
	/**
	 * Take for value the path to an image and resize it if not already done
	 * in the same folder under a hashed name. Returns the new url for the resized version.
	 */
	public function filter($value, array $params)
	{
		if(count($params)!=2)
			throw new TemplateException(sprintf("Filter resizeImage expected two parameters"));

		$media_dir = Cmless::$config['media_dir'];
		$new_width = $params[0];
		$new_height = $params[1];
		$img_path = $media_dir."/".$value;
		$img_url_dir = pathinfo($value)['dirname'];
		$img_name = pathinfo($img_path)['basename'];
		$img_dir = pathinfo($img_path)['dirname'];
		$ext = strtolower(pathinfo($img_path)["extension"]);
		$img_new_name = md5($img_name . $new_width . $new_height).".".$ext;
		$destination = $img_dir."/".$img_new_name;
		$destination_url = $img_url_dir."/".$img_new_name;
		
		if(!in_array($ext, ["bmp", "gif", "jpg", "jpeg", "png", "webp"]))
			throw new TemplateException(sprintf("Filter resizeImage expected image format to be bmp, gif, jpg, jpeg, png or webp."));

		if(!file_exists($img_path))
			throw new TemplateException(sprintf("Filter resizeImage image not found: %s", $img_path));

		if(!file_exists($destination)):
			$dimensions = getimagesize($img_path);
			$width = $dimensions[0];
			$height = $dimensions[1];

			$fnCreate = "imagecreatefrom" . ($ext=="jpg" ? "jpeg" : $ext);
			$fnOutput = "image" . ($ext=="jpg" ? "jpeg" : $ext);

			$original = $fnCreate($img_path);
			$resized = imagecreatetruecolor($new_width, $new_height); 

			if ($ext=="png" || $ext=="gif"):
				imagealphablending($resized, false);
				imagesavealpha($resized, true);
				imagefilledrectangle(
				$resized, 0, 0, $new_width, $new_height,
				imagecolorallocatealpha($resized, 255, 255, 255, 127)
				);
			endif;

			imagecopyresampled($resized, $original, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		
			$fnOutput($resized, $destination);

			imagedestroy($original);
			imagedestroy($resized);
		endif;
		
		return $destination_url;
	}
}

?>