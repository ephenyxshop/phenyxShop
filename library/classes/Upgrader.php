<?php

class UpgraderCore {

	
	public static function synchAdminMeta($metas) {
		
		Configuration::updateValue('PS_ALLOW_ACCENTED_CHARS_URL', 1);
		$idLang = Context::getContext()->language->id;
					
		$metaToCreates = [];
		
		foreach($metas as $meta) {
			
			$current = Meta::getMetaByPage($meta->page, $idLang);
			if(is_array($current) && isset($current['id_meta']) && $current['id_meta'] > 0) {
				continue;
			}
			$metaToCreates[] = $meta;
		}
		
		foreach($metaToCreates as $meta) {
	
			$objet = new Meta();
			$objet->controller = $meta->controller;
			$objet->page = $meta->page;
			$objet->configurable = $meta->configurable;
			$objet->title[$idLang] = $meta->title;
			$objet->description[$idLang] = $meta->description;
			$objet->url_rewrite[$idLang] = $meta->url_rewrite;
			try {
				$result = $objet->add();
			} catch (Exception $ex) {
			
				
			}
			
		}
		
		return true;
		
	}
	
	public static function synchEmployeeMenu($menus) {
		
		$file = fopen("testsynchEmployeeMenu.txt","w");
		
		
		
		$idLang = Context::getContext()->language->id;
		
		$currentMenus = EmployeeMenu::getEmployeeMenu();
		
		$keyReference = [];
		foreach($menus as $menu) {
			$keyReference[] = $menu->reference;
		}
		
		$menuToUpdates = [];
		$menuToCreates = [];
		$menuToDeletes = [];
		
		foreach($menus as $menu) {
			
			$idMenu = EmployeeMenu::getIdEmployeeMenuTypeByRef($menu->reference);
			if($idMenu > 0) {
				$menuToUpdates[] = json_decode(json_encode($menu), true);
			} else {
				$menuToCreates[] = json_decode(json_encode($menu), true);
			}
		}
		
		foreach($currentMenus as $currentMenu) {
			if(in_array($currentMenu['reference'], $keyReference)) {
				continue;
			}
			$menuToDeletes[] = $currentMenu;			
		}
		
		fwrite($file, print_r($menuToCreates, true));
		fwrite($file, print_r($menuToDeletes, true));
		
		foreach($menuToUpdates as $menuToUpdate) {
			
			$idMenu = EmployeeMenu::getIdEmployeeMenuTypeByRef($menuToUpdate['reference']);
			
			if (Validate::isUnsignedId($idMenu)) {
				
				$menu = new EmployeeMenu((int)$idMenu, false);
				
				foreach($menuToUpdate as $key => $value) {
					if (property_exists($menu, $key) && $key != 'id_employee_menu') {
						if ($key == 'id_parent') {
							continue;
						}
						if ($key == 'function' && empty($value)) {
							continue;
						}
						if ($key == 'module' && empty($value)) {
							continue;
						}
						if ($key == 'parent') {
							continue;
						}
						
						$menu->{$key} = $value;
					}
				}
				
				try {
					$result = $menu->update(true, false);
				} catch (Exception $ex) {
					fwrite($file, $ex->getMessage());
				}
				
			}
		}
		
		foreach($menuToCreates as $menuToCreate) {
			
			$menu = new EmployeeMenu();
			foreach($menuToCreate as $key => $value) {
				if (property_exists($menu, $key) && $key != 'id_employee_menu') {
					if ($key == 'id_parent') {
						continue;
					}
					if ($key == 'function' && empty($value)) {
						continue;
					}
					if ($key == 'module' && empty($value)) {
						continue;
					}
					if ($key == 'parent') {
						continue;
					}
					$menu->{$key} = $value;
				}
			}
			$classVars = get_class_vars(get_class($menu));
			$fields = [];

			if (isset($classVars['definition']['fields'])) {
				$fields = $classVars['definition']['fields'];
			}

			foreach ($fields as $field => $params) {

				if (array_key_exists('lang', $params) && $params['lang']) {

					if (property_exists($menu, $field)) {

						foreach (Language::getIDs(false) as $idLang) {

							if (!isset($menuToCreate[$field][(int) $idLang]) || !is_array($menuToCreate[$field])) {
								$menu->{$field} = [];
							}

							$menu->{$field}[(int) $idLang] = $menuToCreate[$field][(int) $idLang];
						}

					}

				}

			}	
			fwrite($file, $menuToCreate['parent'].PHP_EOL);
			$menu->id_parent = EmployeeMenu::getIdEmployeeMenuTypeByRef($menuToCreate['parent']);
			
			
			try {
				$result = $menu->add(true, false, false);
			} catch (Exception $ex) {
				fwrite($file, $ex->getMessage());
			}
			if($result) {
				$replace = [
            		'id_profile' => 1,
            		'id_employee_menu'  => (int) $menu->id,
            		'view'       => 1,
            		'add'        => 1,
            		'edit'       => 1,
            		'delete'     => 1,
        		];
				Db::getInstance()->insert('employee_access', $replace);
			}
		}
		
		foreach($menuToDeletes as $menuToDelete) {
			
			$idMenu = EmployeeMenu::getIdEmployeeMenuTypeByRef($menuToDelete['reference']);
			if (Validate::isUnsignedId($idMenu)) {
				$menu = new EmployeeMenu((int)$idMenu, false);
				$menu->delete();
				
			}
		}
		
		
		
		return true;
	}

	public static function createTopMenu($topMenu) {
		
		$menu = new EmployeeMenu();

		foreach ($topMenu as $key => $value) {

			if (is_array($value)) {
				continue;
			}

			if (property_exists($menu, $key) && $key != 'id') {
				$menu->{$key} = $value;
			}

		}

		$classVars = get_class_vars(get_class($menu));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				if (property_exists($menu, $field)) {

					foreach (Language::getIDs(false) as $idLang) {

						if (!isset($topMenu[$field][(int) $idLang]) || !is_array($topMenu[$field])) {
							$menu->{$field}
							= [];
						}

						$menu->{$field}[(int) $idLang] = $topMenu[$field][(int) $idLang];
					}

				}

			}

		}
		
		$menu->id_parent = EmployeeMenu::getIdEmployeeMenuTypeByRef($topMenu['parent']);

		$result = $menu->add();

		if ($result) {
			return true;
		}

		return false;
	}

	public static function synchTopMenu($topMenu) {

		
		$accesses = $topMenu['accesses'];
		$error = false;
		$idTopbar = EmployeeMenu::getIdEmployeeMenuTypeByRef($topMenu['reference']);
		$topBar = new EmployeeMenu((int) $idTopbar, false);

		if (Validate::isLoadedObject($topBar)) {

			foreach ($topMenu as $key => $value) {

				if (property_exists($topBar, $key) && $key != 'id') {

					if ($key == 'id_parent') {
						continue;
					}

					$topBar->{$key}
					= $value;
				}

			}

			$classVars = get_class_vars(get_class($topBar));
			$fields = [];

			if (isset($classVars['definition']['fields'])) {
				$fields = $classVars['definition']['fields'];
			}

			foreach ($fields as $field => $params) {

				if (array_key_exists('lang', $params) && $params['lang']) {

					if (property_exists($topBar, $field)) {

						foreach (Language::getIDs(false) as $idLang) {

							if (!isset($topMenu[$field][(int) $idLang]) || !is_array($topMenu[$field])) {
								$topBar->{$field}
								= [];
							}

							$topBar->{$field}
							[(int) $idLang] = $topMenu[$field][(int) $idLang];
						}

					}

				}

			}
			
			$topBar->id_parent = EmployeeMenu::getIdEmployeeMenuTypeByRef($topBar->parent);
			
			try {
				$result = $topBar->update(true, false);
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}

			

			if ($result) {
				$right = 0;
				if($topBar->id_parent == 0) {
					$right = 1;
				}
				$replace = [];
        		$replace[] = [
            		'id_profile' => 1,
            		'id_employee_menu'  => (int) $topBar->id,
            		'view'       => 1,
            		'add'        => 1,
            		'edit'       => 1,
            		'delete'     => 1,
        		];
				$profiles = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            		(new DbQuery())
                	->select('`id_profile`')
                	->from('profile')
                	->where('`id_profile` != 1')
        		);
				
				foreach ($profiles as $profile) {
					$key = $profile['id_profile'];
					
            		if(array_key_exists($key, $accesses)) {
						$replace[] = [
               				'id_profile' => (int) $profile['id_profile'],
                			'id_employee_menu'  => (int) $topBar->id,
                			'view'       => (int) $accesses[$profile['id_profile']]['view'],
                			'add'        => (int) $accesses[$profile['id_profile']]['add'],
                			'edit'       => (int) $accesses[$profile['id_profile']]['edit'],
                			'delete'     => (int) $accesses[$profile['id_profile']]['delete'],
            			];
					} else {
						$replace[] = [
               				'id_profile' => (int) $profile['id_profile'],
                			'id_employee_menu'  => (int) $topBar->id,
                			'view'       => $right,
                			'add'        => $right,
                			'edit'       => $right,
                			'delete'     => $right,
            			];
					} 
				}
            	
       			Db::getInstance()->insert('employee_access', $replace, false, true, Db::REPLACE);
				return ['success' => true];
			} else {
				return [
					'success' => false,
					'message' => $error
				];
			}

		}

	}

	public static function synchEducationType($educationType) {

		$idEducationType = EducationType::getIdEducationTypeByRef($educationType['reference']);

		$type = new EducationType($idEducationType);

		if (Validate::isLoadedObject($type)) {

			foreach ($educationType as $key => $value) {

				if (property_exists($type, $key) && $key != 'id') {
					$type->{$key}
					= $value;
				}

			}

			$classVars = get_class_vars(get_class($type));
			$fields = [];

			if (isset($classVars['definition']['fields'])) {
				$fields = $classVars['definition']['fields'];
			}

			foreach ($fields as $field => $params) {

				if (array_key_exists('lang', $params) && $params['lang']) {

					if (property_exists($type, $field)) {

						foreach (Language::getIDs(false) as $idLang) {

							if (!isset($educationType[$field][(int) $idLang]) || !is_array($educationType[$field])) {
								$type->{$field}
								= [];
							}

							$type->{$field}
							[(int) $idLang] = $educationType[$field][(int) $idLang];
						}

					}

				}

			}

			$result = $type->update();

			if ($result) {
				return true;
			}

		}

	}

	public static function createEducationType($educationTypeToPush) {

		$educationType = new EducationType();

		foreach ($educationTypeToPush as $key => $value) {

			if (is_array($value)) {
				continue;
			}

			if (property_exists($educationType, $key) && $key != 'id') {
				$educationType->{$key}
				= $value;
			}

		}

		$classVars = get_class_vars(get_class($educationType));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				if (property_exists($educationType, $field)) {

					foreach (Language::getIDs(false) as $idLang) {

						if (!isset($educationTypeToPush[$field][(int) $idLang]) || !is_array($educationTypeToPush[$field])) {
							$education->{$field}
							= [];
						}

						$educationType->{$field}
						[(int) $idLang] = $educationTypeToPush[$field][(int) $idLang];
					}

				}

			}

		}

		$result = $educationType->add();

		if ($result) {
			return true;
		}

		return false;
	}

	public static function updateEducation($educationToUpdate, $imageToPush, $attributeToUpdates = []) {

		
		$context = Context::getContext();
		$imagesTypes = ImageType::getImagesTypes('education');

		$idEducation = Education::getIdEducationByRef($educationToUpdate['reference']);

		if (Validate::isUnsignedInt($idEducation)) {
			$education = new Education($idEducation);

			if (Validate::isLoadedObject($education)) {

				foreach ($educationToUpdate as $key => $value) {

					if (property_exists($education, $key) && $key != 'id') {
						$education->{$key}	= $value;
					}

				}

				$classVars = get_class_vars(get_class($education));
				$fields = [];

				if (isset($classVars['definition']['fields'])) {
					$fields = $classVars['definition']['fields'];
				}

				foreach ($fields as $field => $params) {

					if (array_key_exists('lang', $params) && $params['lang']) {

						if (property_exists($education, $field)) {

							foreach (Language::getIDs(false) as $idLang) {

								if (!isset($educationToUpdate[$field][(int) $idLang]) || !is_array($educationToUpdate[$field])) {
									$education->{$field} = [];
								}

								$education->{$field}[(int) $idLang] = $educationToUpdate[$field][(int) $idLang];
							}

						}

					}

				}

				$education->id_education_prerequis = EducationPrerequis::getIdPrerequisByRef($education->reference);

				$result = $education->update();
				
				$idCover = null;
				$cover =  Education::getCover($education->id);
				if(is_array($cover) && count($cover)) {
					$idCover = $cover ['id_image_education'];
				}
				
				
				$imagesTypes = ImageType::getImagesTypes('education');
				$generateHighDpiImages = (bool) Configuration::get('PS_HIGHT_DPI');
				
				foreach ($imageToPush as $key => $image) {
					
					if ($image['cover'] == 1) {
						
						if (file_exists(_PS_UPLOAD_DIR_ . $key)) {
							$savePath = _PS_UPLOAD_DIR_ . $key;
							if($idCover == $image['id_image_education']) {
								$imageObj = new ImageEducation($idCover);
								$newPath = $imageObj->getPathForCreation();
								if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder())) {
									$toDel = scandir(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder());
									foreach ($toDel as $d) {
										foreach ($imagesTypes as $imageType) {
											if (preg_match('/^[0-9]+\-' . $imageType['name'] . '\.(jpg|webp)$/', $d) || (count($imagesTypes) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.(jpg|webp)$/', $d))) {
												if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $d)) {
                            						unlink(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $d);
                        						}
                    						}
                						}
            						}
								
            						if (file_exists($newPath . '.jpg')) {
										unlink($newPath . '.jpg');
            						}
									if (file_exists($newPath . '.webp')) {
                						unlink($newPath . '.webp');
            						}
								}
								copy($savePath, $newPath.'.jpg');
								$imageObj->id_education = (int) ($education->id);
								$imageObj->reference = $image['image_reference'];
        						$imageObj->position = 1;
        						$imageObj->cover = 1;
								foreach (Language::getIDs(false) as $idLang) {
            						$imageObj->legend[(int) $idLang] = $education->name[(int) $idLang];
        						}
        						$imageObj->update();
								
								foreach ($imagesTypes as $imageType) {
            						ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '.' . $imageObj->image_format, $imageType['width'], $imageType['height'], $imageObj->image_format);
            						if ($generateHighDpiImages) {
                						ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $imageObj->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $imageObj->image_format);
            						}
								}
								unlink($savePath);
        						unset($savePath);
        						$imageObj->update();
								
							} else if($idCover > 0) {
									
									$imageObj = new ImageEducation((int) $idCover);
									$imageObj->reference = $image['image_reference'];
									$imageObj->id_education = (int) ($education->id);
									$imageObj->position = 1;
        							$imageObj->cover = 1;
									foreach (Language::getIDs(false) as $idLang) {
            							$imageObj->legend[(int) $idLang] = $education->name[(int) $idLang];
        							}
									
        							$imageObj->update();
									$newPath = $imageObj->getPathForCreation();
								
									if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder())) {
            							$toDel = scandir(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder());
            							foreach ($toDel as $d) {
											foreach ($imagesTypes as $imageType) {
												if (preg_match('/^[0-9]+\-' . $imageType['name'] . '\.(jpg|webp)$/', $d) || (count($imagesTypes) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.(jpg|webp)$/', $d))) {
													if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $d)) {
                            							unlink(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $d);
                        							}
                    							}
											}
            							}
            							
            							if (file_exists($newPath . '.jpg')) {
											unlink($newPath . '.jpg');
            							}
										if (file_exists($newPath . '.webp')) {
                							unlink($newPath . '.webp');
            							}
        							}
									copy($savePath, $newPath.'.jpg');
									foreach ($imagesTypes as $imageType) {
            							ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '.' . $imageObj->image_format, $imageType['width'], $imageType['height'], $imageObj->image_format);
            							if ($generateHighDpiImages) {
                							ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $imageObj->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $imageObj->image_format);
            							}
									}
									unlink($savePath);
        							unset($savePath);
							} else {
								$imageObj = new ImageEducation();
								$imageObj->reference = $image['image_reference'];
								$imageObj->id_education = (int) ($education->id);
								$imageObj->position = 1;
        						$imageObj->cover = 1;
								foreach (Language::getIDs(false) as $idLang) {
            						$imageObj->legend[(int) $idLang] = $education->name[(int) $idLang];
        						}
        						$imageObj->add();
								$newPath = $imageObj->getPathForCreation();
								copy($savePath, $newPath.'.jpg');
								foreach ($imagesTypes as $imageType) {
            							ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '.' . $imageObj->image_format, $imageType['width'], $imageType['height'], $imageObj->image_format);
            							if ($generateHighDpiImages) {
                							ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $imageObj->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $imageObj->image_format);
            							}
									}
								unlink($savePath);
        						unset($savePath);
							}
						}
					}
				}

				if (count($attributeToUpdates) > 0) {

					foreach ($attributeToUpdates as $declinaison) {
						$idDeclinaison = Declinaison::getIdDeclinaisonByRef($declinaison['reference']);

						if (Validate::isUnsignedInt($idDeclinaison)) {
							$combination = new Declinaison($idDeclinaison);
							 

							if (Validate::isLoadedObject($combination)) {
								
								foreach ($declinaison as $key => $value) {

									if (property_exists($combination, $key) && $key != 'id' && $key != 'id_education') {
										$combination->{$key}= $value;
									}

								}

								$classVars = get_class_vars(get_class($combination));
								$fields = [];

								if (isset($classVars['definition']['fields'])) {
									$fields = $classVars['definition']['fields'];
								}

								foreach ($fields as $field => $params) {

									if (array_key_exists('lang', $params) && $params['lang']) {

										if (property_exists($combination, $field)) {

											foreach (Language::getIDs(false) as $idLang) {

												if (!isset($declinaison[$field][(int) $idLang]) || !is_array($declinaison[$field])) {
													$combination->{$field}
													= [];
												}

												$combination->{$field}[(int) $idLang] = $declinaison[$field][(int) $idLang];
											}

										}

									}

								}

							}

							$result = $combination->update();
						
							foreach ($imageToPush as $key => $image) {

								if ($image['declinaison_reference'] == $combination->reference) {
									if (file_exists(_PS_UPLOAD_DIR_ . $key)) {
										$savePath = _PS_UPLOAD_DIR_ . $key;
										$imageExist = ImageEducation::imageExist($image['id_image_education']);
										if(is_array($imageExist && count($imageExist))) {
											$imageObj = new ImageEducation($image['id_image_education']);
											$imageObj->reference = $image['image_reference'];
											$imageObj->update();
											if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder())) {
            									$toDel = scandir(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder());
            									foreach ($toDel as $d) {
													foreach ($imagesTypes as $imageType) {
														if (preg_match('/^[0-9]+\-' . $imageType['name'] . '\.(jpg|webp)$/', $d) || (count($imagesTypes) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.(jpg|webp)$/', $d))) {
															if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $d)) {
                            									unlink(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $d);
                        									}
                    									}
													}
            									}
            									if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $key . '.jpg')) {
                									unlink(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $key . '.jpg');
												}
            									if (file_exists(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $key . '.webp')) {
                									unlink(_PS_EDUC_IMG_DIR_ . $imageObj->getImgFolder() . $key . '.webp');
            									}
        									}
											$newPath = $imageObj->getPathForCreation();
											copy($savePath, $newPath.'.jpg');
        									foreach ($imagesTypes as $imageType) {
												ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '.' . $imageObj->image_format, $imageType['width'], $imageType['height'], $imageObj->image_format);
												if ($generateHighDpiImages) {
                									ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $imageObj->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $imageObj->image_format);
            									}
											}
        									unlink($savePath);
        									unset($savePath);
        									$imageObj->update();
										} else {
											$imageObj = new ImageEducation();
											$imageObj->reference = $image['image_reference'];
        									$imageObj->id_education = (int) ($education->id);
        									$imageObj->position = 0;
        									$imageObj->cover = 0;
											foreach (Language::getIDs(false) as $idLang) {
            									$imageObj->legend[(int) $idLang] = $combination->name[(int) $idLang];
        									}
											if ($imageObj->add()) {
												$newPath = $imageObj->getPathForCreation();
												copy($savePath, $newPath.'.jpg');
            									foreach ($imagesTypes as $imageType) {
                									ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '.' . $imageObj->image_format, $imageType['width'], $imageType['height'], $imageObj->image_format);
													if ($generateHighDpiImages) {
                    									ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $imageObj->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $image->image_format);
                									}
												}
            									$sql = 'INSERT INTO `' . _DB_PREFIX_ . 'education_attribute_image` (`id_education_attribute`, `id_image`) VALUES(' . (int) $combination->id . ', ' . (int) $imageObj->id . ')';
												DB::getInstance()->execute($sql);
        									}
										}
									}
								}
							}
						} else {
							$combination = new Declinaison();
							
							foreach ($declinaison as $key => $value) {

								if (property_exists($combination, $key) && $key != 'id') {
									$combination->{$key}	= $value;
								}

							}

							$classVars = get_class_vars(get_class($combination));
							$fields = [];

							if (isset($classVars['definition']['fields'])) {
								$fields = $classVars['definition']['fields'];
							}

							foreach ($fields as $field => $params) {

								if (array_key_exists('lang', $params) && $params['lang']) {

									if (property_exists($combination, $field)) {

										foreach (Language::getIDs(false) as $idLang) {

											if (!isset($declinaison[$field][(int) $idLang]) || !is_array($declinaison[$field])) {
												$combination->{$field} 	= [];
											}

											$combination->{$field}[(int) $idLang] = $declinaison[$field][(int) $idLang];
										}

									}

								}

							}

							$combination->id_education = $education->id;
							$combination->id_education_prerequis = EducationPrerequis::getIdPrerequisByRef($combination->reference);

							$result = $combination->add();

							if ($result) {
								$sqlValues = [];

								foreach ($combination->combinations as $key => $value) {
									$sqlValues[] = [
										'id_attribute'           => (int) $value['id_attribute'],
										'id_education_attribute' => (int) $combination->id,
									];
								}

								Db::getInstance()->insert('education_attribute_combination', $sqlValues);

								foreach ($imageToPush as $key => $value) {

									if ($value['declinaison_reference'] == $combination->reference) {

										if (file_exists(_PS_UPLOAD_DIR_ . $key)) {
											$image = new ImageEducation();
											$image->id_education = (int) ($education->id);
											$image->position = 0;
											$image->cover = $value['cover'];

											foreach (Language::getIDs(false) as $idLang) {
												$image->legend[(int) $idLang] = $combination->name[(int) $idLang];
											}

											if ($image->add()) {
												$savePath = _PS_UPLOAD_DIR_ . $key;
												$imagesTypes = ImageType::getImagesTypes('education');
												$newPath = $image->getPathForCreation();
												$generateHighDpiImages = (bool) Configuration::get('PS_HIGHT_DPI');

												foreach ($imagesTypes as $imageType) {
													ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format);

													if ($generateHighDpiImages) {
														ImageManager::resize($savePath, $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $image->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $image->image_format);
													}

												}

												$sql = 'INSERT INTO `' . _DB_PREFIX_ . 'education_attribute_image`
													(`id_education_attribute`, `id_image`)
													VALUES(' . (int) $combination->id . ', ' . (int) $image->id . ')';
												DB::getInstance()->execute($sql);

												unlink($savePath);
												unset($savePath);
											}

										}

									}

								}

							}
						}
					}
				}
				return $education->id;
			}

		}

	}
	
	public static function createFrontTopMenu($topMenu) {

		$menu = new TopMenu();

		foreach ($topMenu as $key => $value) {

			if (is_array($value)) {
				continue;
			}

			if (property_exists($menu, $key) && $key != 'id') {
				$menu->{$key}	= $value;
			}

		}

		$classVars = get_class_vars(get_class($menu));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				if (property_exists($menu, $field)) {

					foreach (Language::getIDs(false) as $idLang) {

						if (!isset($topMenu[$field][(int) $idLang]) || !is_array($topMenu[$field])) {
							$menu->{$field}
							= [];
						}

						$menu->{$field}	[(int) $idLang] = $topMenu[$field][(int) $idLang];
					}

				}

			}

		}

		$result = $menu->add();

		if ($result) {
			return true;
		}

		return false;
	}
	
	public static function createFrontTopMenuWrap($topMenu) {

		$menu = new TopMenuColumnWrap();

		foreach ($topMenu as $key => $value) {

			if (is_array($value)) {
				continue;
			}

			if (property_exists($menu, $key) && $key != 'id') {
				$menu->{$key}	= $value;
			}

		}

		$classVars = get_class_vars(get_class($menu));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				if (property_exists($menu, $field)) {

					foreach (Language::getIDs(false) as $idLang) {

						if (!isset($topMenu[$field][(int) $idLang]) || !is_array($topMenu[$field])) {
							$menu->{$field}
							= [];
						}

						$menu->{$field}	[(int) $idLang] = $topMenu[$field][(int) $idLang];
					}

				}

			}

		}
		
		$menu->id_topmenu = TopmenuColumnWrap::getIdTopMenuByColumnWrapRef($menu->parent_reference);
		

		$result = $menu->add();

		if ($result) {
			return true;
		}

		return false;
	}
	
	public static function createFrontTopMenuColumn($topMenu) {

		$menu = new TopMenuColumn();

		foreach ($topMenu as $key => $value) {

			if (is_array($value)) {
				continue;
			}

			if (property_exists($menu, $key) && $key != 'id') {
				$menu->{$key}	= $value;
			}

		}

		$classVars = get_class_vars(get_class($menu));
		$fields = [];

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		foreach ($fields as $field => $params) {

			if (array_key_exists('lang', $params) && $params['lang']) {

				if (property_exists($menu, $field)) {

					foreach (Language::getIDs(false) as $idLang) {

						if (!isset($topMenu[$field][(int) $idLang]) || !is_array($topMenu[$field])) {
							$menu->{$field}
							= [];
						}

						$menu->{$field}	[(int) $idLang] = $topMenu[$field][(int) $idLang];
					}

				}

			}

		}
		
		$menu->id_topmenu = TopmenuColumn::getIdTopMenuByColumnRef($menu->parent_reference);
		$menu->id_topmenu_columns_wrap = TopmenuColumn::getIdParentWrapByRef($menu->wrap_reference);

		$result = $menu->add();

		if ($result) {
			return true;
		}

		return false;
	}


	public static function executeSqlRequest($query, $method) {

		switch ($method) {
		case 'execute':
			return Db::getInstance()->execute($query);
			break;
		case 'executeS':
			return Db::getInstance()->executeS($query);
			break;
		case 'getValue':
			return Db::getInstance()->getValue($query);
			break;
		case 'getRow':
			return Db::getInstance()->getRow($query);
			break;
		}

	}

}
