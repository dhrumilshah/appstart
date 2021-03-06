<?php
class ModuleImageGallery1_IndexController extends Zend_Controller_Action {
	var $_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		$modulesMapper = new Admin_Model_Mapper_Module ();
		$module = $modulesMapper->fetchAll ( "name ='module-image-gallery-1'" );
		if (is_array ( $module )) {
			$this->_module_id = $module [0]->getModuleId ();
		}
	}
	public function indexAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->addlink = $this->view->url ( array (
				"module" => "module-image-gallery-1",
				"controller" => "index",
				"action" => "add" 
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "module-image-gallery-1",
				"controller" => "index",
				"action" => "reorder" 
		), "default", true );
		$this->view->addcategory = $this->view->url ( array (
				"module" => "module-image-gallery-1",
				"controller" => "category",
				"action" => "index" 
		), "default", true );
		$this->view->bulkupload = $this->view->url ( array (
				"module" => "module-image-gallery-1",
				"controller" => "index",
				"action" => "bulk-upload"
		), "default", true );
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$categories = $this->_getCategories ($customer_id);
		$this->view->categories = $categories;
	}
	public function bulkUploadAction() {
		$request = $this->getRequest ();
	
		if ($this->_request->isPost ()) {
			$response = array ();
			if ($request->getParam ( "upload", "" ) != "") {
				$this->_helper->layout ()->disableLayout ();
				$this->_helper->viewRenderer->setNoRender ();
	
				$image_name = $request->getParam ( "image[]", "" );
				$category_id = $request->getParam ( "category_id", "" );
	
				$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
				$user_id = Standard_Functions::getCurrentUser ()->user_id;
				$date_time = Standard_Functions::getCurrentDateTime ();
	
				$upload_dir = Standard_Functions::getResourcePath () . "module-image-gallery-1/thumb/" . $customer_id . "/";
				$source_dir = Standard_Functions::getResourcePath () . "module-image-gallery-1/images/";
				$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
				$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
	
				try {
					$adapter = new Zend_File_Transfer_Adapter_Http ();
					$adapter->setDestination ( Standard_Functions::getResourcePath () . "module-image-gallery-1/images" );
					foreach ( $adapter->getFileInfo () as $info ) {
						if ($adapter->receive ()) {
							$image_path = $info ["name"];
								
							$model = new ModuleImageGallery1_Model_ModuleImageGallery1 ();
							$maxOrder = $mapper->getNextOrder ( $category_id );
							$model->setOrder ( $maxOrder + 1 );
							$model->setModuleImageGalleryCategory1Id ( $category_id );
							$model->setCustomerId ( $customer_id );
							$model->setCreatedBy ( $user_id );
							$model->setCreatedAt ( $date_time );
							$model->setLastUpdatedBy ( $user_id );
							$model->setLastUpdatedAt ( $date_time );
							$model->setStatus ( 1 );
							$model = $model->save ();
								
							// Save image Details
							$module_image_gallery_id = $model->get ( "module_image_gallery_1_id" );
							$result ["module_image_gallery_1_id"] = $module_image_gallery_id;
							$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
							$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
							if (is_array ( $modelLanguages )) {
								$is_uploaded_image = false;
								foreach ( $modelLanguages as $languages ) {
									$modelDetails = new ModuleImageGallery1_Model_ModuleImageGalleryDetail1 ();
									$modelDetails->setModuleImageGallery1Id ( $module_image_gallery_id );
									$modelDetails->setLanguageId ( $languages->getLanguageId () );
									if (! is_dir ( $upload_dir )) {
										mkdir ( $upload_dir, 755 );
									}
									if (! $is_uploaded_image && $image_path != "") {
										$filename = $this->moveUploadFile ( $source_dir, $upload_dir, $image_path );
										$modelDetails->setImagePath ( $filename );
										$ext_image_path = array_pop ( explode ( ".", $image_path ) );
										if ($image_path != "") {
											$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
										}
										$result ["image_path"] = $this->view->baseurl ( "resource/module-image-gallery-1/thumb/" . $customer_id . "/" . $image_path );
										$response [] = $result;
	
										$is_uploaded_image = true;
									} else if ($image_path != "") {
										$modelDetails->setImagePath ( $filename );
									}
									$modelDetails = $modelDetails->save ();
								}
							}
						}
					}
					$mapper->getDbTable ()->getAdapter ()->commit ();
					$response = array (
							"success" => 'true',
							"result" => $response
					);
				} catch ( Exception $ex ) {
					$mapper->getDbTable ()->getAdapter ()->rollBack ();
					$response = array (
							"errors" => "Error Occured"
					);
				}
				echo Zend_Json::encode ( $response );
				exit ();
			} else {
				// Save Details
				$array_module_image_gallery_id = $request->getParam("module_image_gallery_1_id",null);
	
				if(is_array($array_module_image_gallery_id)) {
					$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
					$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
					try {
						foreach($array_module_image_gallery_id as $module_image_gallery_id) {
							$mapperDetails = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryDetail1();
							$modelDetails = $mapperDetails->fetchAll("module_image_gallery_1_id = " . $module_image_gallery_id);
							if(is_array($modelDetails)) {
								foreach($modelDetails as $details) {
									$details->setTitle($request->getParam("title_" . $module_image_gallery_id,""));
									$details->setDescription($request->getParam("description_" . $module_image_gallery_id,""));
									$details->save();
								}
							}
						}
	
						$mapper->getDbTable ()->getAdapter ()->commit ();
						$response = array (
								"success" => true
						);
					} catch (Exception $ex) {
						$mapper->getDbTable ()->getAdapter ()->rollBack ();
						$response = array (
								"errors" => "Error Occured"
						);
					}
				}
				echo Zend_Json::encode ( $response );
				exit ();
			}
		}
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$options = array (
				"" => 'Select Category'
		);
		$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategory1();
		$models = $mapper->fetchAll ("customer_id =" .$customer_id);
		if($models){
			foreach($models as $key=>$records){
				$detailMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategoryDetail1();
				$detailModels = $detailMapper->fetchAll("module_image_gallery_category_1_id =" .$records->getModuleImageGalleryCategory1Id());
				foreach($detailModels as $categories){
					$options[$categories->getModuleImageGalleryCategory1Id()] = $categories->getTitle();
				}
			}
		}
		$this->view->categories = $options;
	}
	public function addAction() {
		$form = new ModuleImageGallery1_Form_ModuleImageGallery ();
		$action = $this->view->url ( array (
				"module" => "module-image-gallery-1",
				"controller" => "index",
				"action" => "save" 
		), "default", true );
		$form->setAction ( $action );
		$form->setMethod ( 'POST' );
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml" 
		) );
		$this->view->form = $form;
		$mode = "add";
		$this->view->mode = $mode;
		$this->render ( "add-edit" );
	}
	public function editAction() {
		$form = new ModuleImageGallery1_Form_ModuleImageGallery ();
		$action = $this->view->url ( array (
				"module" => "module-image-gallery-1",
				"controller" => "index",
				"action" => "save" 
		), "default", true );
		$form->setAction ( $action );
		$form->setMethod ( 'POST' );
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml" 
		) );
		$this->view->form = $form;
		$request = $this->getRequest ();
		$keywords = array ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
			$module_image_gallery_1_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			$data = $mapper->find ( $module_image_gallery_1_id )->toArray ();
			$form->populate ( $data );
			$datadetails = array ();
			$detailsMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryDetail1 ();
			if ($detailsMapper->countAll ( "module_image_gallery_1_id = " . $module_image_gallery_1_id . " AND language_id = " . $language_id ) > 0) {
				// Record For Language Found
				$dataDetails = $detailsMapper->getDbTable ()->fetchAll ( "module_image_gallery_1_id = " . $module_image_gallery_1_id . " AND language_id = " . $language_id )->toArray ();
				$keywords = $dataDetails [0] ['keywords'];
				$keywords = explode ( ",", $keywords );
			} else {
				// Record For Language Not Found
				$dataDetails = $detailsMapper->getDbTable ()->fetchAll ( "module_image_gallery_1_id = " . $module_image_gallery_1_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["module_image_gallery_detail_1_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
				// $this->view->category = $dataDetails[0]['title'];
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
				$image_path = $dataDetails [0] ['image_path'];
				$image_uri = "resource/module-image-gallery-1/thumb/" . $customer_id . "/";
				$ext_image_path = array_pop ( explode ( ".", $image_path ) );
				if ($image_path != "" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
					$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
				}
				$this->view->image_thumb = $this->view->baseUrl ( $image_uri . "/" . $image_path );
			}
			$action = $this->view->url ( array (
					"module" => "module-image-gallery-1",
					"controller" => "index",
					"action" => "save",
					"id" => $request->getParam ( "id", "" ) 
			), "default", true );
			$form->setAction ( $action );
		} else {
			$this->_redirect ( '/' );
		}
		$this->view->form = $form;
		$this->view->keywords = $keywords;
		$this->view->assign ( array (
				"partial" => "index/partials/edit.phtml" 
		) );
		$this->render ( "add-edit" );
	}
	public function saveAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$form = new ModuleImageGallery1_Form_ModuleImageGallery ();
		$request = $this->getRequest ();
		$response = array ();
		if ($this->_request->isPost ()) {
			if ($request->getParam ( "upload", "" ) != "") {
				$image_name = $request->getParam ( "image_name", "" );
				$adapter = new Zend_File_Transfer_Adapter_Http ();
				$adapter->setDestination ( Standard_Functions::getResourcePath () . "module-image-gallery-1/images" );
				$adapter->receive ();
				if ($adapter->getFileName ( $image_name ) != "") {
					$response = array (
							"success" => array_pop ( explode ( '\\', $adapter->getFileName ( $image_name ) ) ) 
					);
				} else {
					$response = array (
							"errors" => "Error Occured" 
					);
				}
				echo Zend_Json::encode ( $response );
				exit ();
			}
			$form->removeElement ( "image" );
			$datas = $this->_request->getParam ( 'data' );
			foreach ( $datas as $data ) {
				if ($form->isValid ( $data )) {
					try {
						$allFormValues = $form->getValues ();
						$tag = "";
						$arrTags = $data ["arrtag"];
						if ($arrTags != "") {
							foreach ( $arrTags as $tags ) {
								$tag .= ($tag != "" ? "," : "") . $tags;
							}
						}
						$keywords = $tag;
						$category_id = $data['module_image_gallery_category_1_id'];;
						$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
						$user_id = Standard_Functions::getCurrentUser ()->user_id;
						$date_time = Standard_Functions::getCurrentDateTime ();
						$image_path = $data[image_path];
						$upload_dir = Standard_Functions::getResourcePath () . "module-image-gallery-1/thumb/" . $customer_id . "/";
						$source_dir = Standard_Functions::getResourcePath () . "module-image-gallery-1/images/";
						$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
						$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
						$model = new ModuleImageGallery1_Model_ModuleImageGallery1 ( $allFormValues );
						if ($data['module_image_gallery_1_id'] == "") {
							// Add new image
							$maxOrder = $mapper->getNextOrder ( $category_id );
							$model->setOrder ( $maxOrder + 1 );
							$model->setCustomerId ( $customer_id );
							$model->setCreatedBy ( $user_id );
							$model->setCreatedAt ( $date_time );
							$model->setLastUpdatedBy ( $user_id );
							$model->setLastUpdatedAt ( $date_time );
							$model = $model->save ();
							// Save image Details
							$module_image_gallery_id = $model->get ( "module_image_gallery_1_id" );
							$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
							$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
							if (is_array ( $modelLanguages )) {
								$is_uploaded_image = false;
								foreach ( $modelLanguages as $languages ) {
									$modelDetails = new ModuleImageGallery1_Model_ModuleImageGalleryDetail1 ( $data );
									if ($keywords != "") {
										$modelDetails->setKeywords ( $keywords );
									}
									$modelDetails->setModuleImageGallery1Id ( $module_image_gallery_id );
									$modelDetails->setLanguageId ( $languages->getLanguageId () );
									if (! is_dir ( $upload_dir )) {
										mkdir ( $upload_dir, 755 );
									}
									if (! $is_uploaded_image && $image_path != "") {
										$filename = $this->moveUploadFile ( $source_dir, $upload_dir, $image_path );
										$modelDetails->setImagePath ( $filename );
										$is_uploaded_image = true;
									} else if ($image_path != "") {
										$modelDetails->setImagePath ( $filename );
									}
									$modelDetails = $modelDetails->save ();
								}
							}
						} else {
							// update image
							$model->setLastUpdatedBy ( $user_id );
							$model->setLastUpdatedAt ( $date_time );
							$model = $model->save ();
							// update image details
							$mapperDetails = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryDetail1();
							$modelDetails = $mapperDetails->find($data["module_image_gallery_detail_1_id"]);
							if (! is_dir ( $upload_dir )) {
								mkdir ( $upload_dir, 755 );
							}
							if ($image_path != "") {
								$filename = $this->moveUploadFile ( $source_dir, $upload_dir, $image_path );
								$modelDetails->setImagePath ( $filename );
								$is_uploaded_image = true;
							}
							if ($keywords != "") {
								$modelDetails->setKeywords ( $keywords );
							}
							$modelDetails = $modelDetails->save ();
						}
						$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
						$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
						if (is_array ( $customermodule )) {
							$customermodule = $customermodule [0];
							$customermodule->setIsPublish ( "NO" );
							$customermodule->save ();
						}
						$mapper->getDbTable ()->getAdapter ()->commit ();
						$response = array (
								"success" => $model->toArray () 
						);
					} catch ( Exception $ex ) {
						$mapper->getDbTable ()->getAdapter ()->rollBack ();
						$response = $ex->getMessage ();
					}
				} else {
					echo "form is not valid";
					exit ();
				}
			}
		} else {
			$errors = $form->getMessages ();
			foreach ( $errors as $name => $error ) {
				$errors [$name] = $error [0];
			}
			$response = array (
					"errors" => $errors 
			);
		}
		$this->_helper->json ( $response );
	}
	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		if (($module_image_gallery_1_id = $request->getParam ( "id", "" )) != "") {
			$model = new ModuleImageGallery1_Model_ModuleImageGallery1 ();
			if ($model) {
				try {
					$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
					$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$detailmapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryDetail1 ();
					$data = $mapper->fetchAll ( "module_image_gallery_1_id = " . $module_image_gallery_1_id );
					if ($data) {
						foreach ( $data as $image ) {
							$dataDetails = $detailmapper->fetchAll ( "module_image_gallery_1_id =" . $image->getModuleImageGallery1Id () );
							foreach ( $dataDetails as $dataDetail ) {
								$deletedRows = $dataDetail->delete ();
							}
							$image->delete ();
						}
					}
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
					$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
					if (is_array ( $customermodule )) {
						$customermodule = $customermodule [0];
						$customermodule->setIsPublish ( "NO" );
						$customermodule->save ();
					}
					$mapper->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows 
							) 
					);
				} catch ( Exception $e ) {
					$mapper->getDbTable ()->getAdapter ()->rollBack ();
					$response = array (
							"errors" => array (
									"message" => $e->getMessage () 
							) 
					);
				}
			} else {
				$response = array (
						"errors" => array (
								"message" => "No user to delete." 
						) 
				);
			}
		} else {
			$this->_redirect ( '/' );
		}
		$this->_helper->json ( $response );
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		$category_id = $request->getParam ( "id", "" );
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"mig" => "module_image_gallery_1" 
		), array (
				"mig.module_image_gallery_1_id" => "module_image_gallery_1_id",
				"mig.module_image_gallery_category_1_id" => "mig.module_image_gallery_category_1_id",
				"mig.status" => "status",
				"mig.order" => "order" 
		) )->joinLeft ( array (
				"migd" => "module_image_gallery_detail_1" 
		), "migd.module_image_gallery_1_id = mig.module_image_gallery_1_id AND migd.language_id = " . $active_lang_id, array (
				"migd.module_image_gallery_detail_1_id" => "module_image_gallery_detail_1_id",
				"migd.title" => "title",
				"migd.image_path" => "image_path" 
		) );
		if ($category_id != "") {
			$select = $select->where ( "mig.module_image_gallery_category_1_id ='" . $category_id . "' AND mig.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		} else {
			$select = $select->where ( "	mig.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		}
		$response = $mapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions' 
						),
						'replace' => array (
								'mig.status' => array (
										'1' => $this->view->translate ( 'Active' ),
										'0' => $this->view->translate ( 'Inactive' ) 
								) 
						) 
				) 
		), "customer_id=" . Standard_Functions::getCurrentUser ()->customer_id, $select );
		$mapper = new Admin_Model_Mapper_CustomerLanguage ();
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"l" => "language" 
		), array (
				"l.language_id" => "language_id",
				"l.title" => "title",
				"logo" => "logo" 
		) )->joinLeft ( array (
				"cl" => "customer_language" 
		), "l.language_id = cl.language_id", array (
				"cl.customer_id" 
		) )->where ( "cl.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		$languages = $mapper->getDbTable ()->fetchAll ( $select )->toArray ();
		$rows = $response ['aaData'];
		foreach ( $rows as $rowId => $row ) {
			$edit = array ();
			if ($row [4] ["migd.module_image_gallery_detail_1_id"] == "") {
				$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryDetail1 ();
				$details = $mapper->fetchAll ( "module_image_gallery_1_id=" . $row [4] ["mig.module_image_gallery_1_id"] . " AND language_id=" . $default_lang_id );
				if (is_array ( $details )) {
					$details = $details [0];
					$row [4] ["migd.title"] = $row [1] = $details->getTitle ();
					$row [4] ["migd.image_path"] = $row [0] = $details->getImagePath ();
				}
			}
			$response ['aaData'] [$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "module-image-gallery-1",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [4] ["mig.module_image_gallery_1_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '"><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "module-image-gallery-1",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [4] ["mig.module_image_gallery_1_id"] 
			), "default", true );
			
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">' . implode ( "", $edit ) . '</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
			
			$image_path = $row [4] ["migd.image_path"];
			$image_uri = "resource/module-image-gallery-1/thumb/" . $customer_id . "/";
			$ext_image_path = array_pop ( explode ( ".", $image_path ) );
			if ($image_path != "" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
				$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
			}
			$response ['aaData'] [$rowId] [1] = "<img src='" . $this->view->baseurl ( $image_uri . $image_path ) . "' />";
			$response ['aaData'] [$rowId] [4] = $defaultEdit . $sap . $delete;
		}
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	private function moveUploadFile($source_dir, $dest_dir, $filename) {
		$source_file_name = $filename;
		$expension = array_pop ( explode ( ".", $filename ) );
		try {
			$i = 1;
			while ( file_exists ( $dest_dir . $filename ) ) {
				$filename = str_replace ( "." . $expension, "_" . $i ++ . "." . $expension, $source_file_name );
			}
			if (! is_dir ( $dest_dir )) {
				mkdir ( $dest_dir, 755 );
			}
			while ( ! file_exists ( $source_dir . $source_file_name ) ) {
			}
			
			if (copy ( $source_dir . $source_file_name, $dest_dir . $filename )) {
			}
			$thumbname = str_replace ( "." . $expension, "_thumb." . $expension, $filename );
			$this->generateThumb ( $dest_dir . $filename, $dest_dir . $thumbname, 0, 100 );
		} catch ( Exception $ex ) {
		}
		return $filename;
	}
	public function generateThumb($src, $dest, $destWidth = 0, $destHeight = 0) {
		/* read the source image */
		$stype = array_pop ( explode ( ".", $src ) );
		switch ($stype) {
			case 'gif' :
				$source_image = imagecreatefromgif ( $src );
				break;
			case 'jpg' :
			case 'jpeg' :
				$source_image = imagecreatefromjpeg ( $src );
				break;
			case 'png' :
				$source_image = imagecreatefrompng ( $src );
				break;
		}
		
		$width = imagesx ( $source_image );
		$height = imagesy ( $source_image );
		
		$desired_height = 0;
		$desired_width = 0;
		if ($destWidth == 0) {
			$desired_height = $destHeight;
			$desired_width = floor ( $width * ($destHeight / $height) );
		} else {
			$desired_height = floor ( $destHeight * ($destWidth / $width) );
			$desired_width = $destWidth;
		}
		
		/* create a new, "virtual" image */
		$virtual_image = imagecreatetruecolor ( $desired_width, $desired_height );
		imagealphablending ( $virtual_image, false );
		imagesavealpha ( $virtual_image, true );
		
		imagealphablending ( $source_image, true );
		/* copy source image at a resized size */
		imagecopyresampled ( $virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height );
		
		/* create the physical thumbnail image to its destination */
		switch ($stype) {
			case 'gif' :
				imagegif ( $virtual_image, $dest );
				break;
			case 'jpg' :
			case 'jpeg' :
				imagejpeg ( $virtual_image, $dest );
				break;
			case 'png' :
				imagepng ( $virtual_image, $dest, 0 );
				break;
		}
	}
	public function _getCategories($customer_id) {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$options = array (
				"" => 'Select Category' 
		);
		$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategory1 ();
		$models = $mapper->fetchAll ("customer_id =".$customer_id);
		if (is_array ( $models )) {
			foreach ( $models as $key => $records ) {
				$detailMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategoryDetail1 ();
				$detailModels = $detailMapper->fetchAll ( "language_id ='".$active_lang_id."' AND module_image_gallery_category_1_id =" . $records->getModuleImageGalleryCategory1Id () );
				foreach ( $detailModels as $categories ) {
					$options [$categories->getModuleImageGalleryCategory1Id ()] = $categories->getTitle ();
				}
			}
		}
		return $options;
	}
	public function reorderAction() {
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$categories = $this->_getCategories ($customer_id);
		$this->view->categories = $categories;
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$request = $this->getRequest ();
		$category_id = $request->getParam ( "id", 1 );
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$detailMapper = new ModuleCms_Model_Mapper_ModuleCmsDetail ();
		if ($this->_request->isPost ()) {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNoRender ();
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			$response = array ();
			
			$order = $this->_request->getParam ( "order" );
			$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
			$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
			try {
				foreach ( $order as $key => $value ) {
					$model = $mapper->find ( $value );
					$model->setOrder ( $key );
					$model->setLastUpdatedBy ( $user_id );
					$model->setLastUpdatedAt ( $date_time );
					$model->save ();
				}
				// set is pulish to false
				$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
				$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
				if (is_array ( $customermodule )) {
					$customermodule = $customermodule [0];
					$customermodule->setIsPublish ( "NO" );
					$customermodule->save ();
				}
				$mapper->getDbTable ()->getAdapter ()->commit ();
				if ($model && $model->getModuleImageGallery1Id () != "") {
					$response = array (
							"success" => true 
					);
				}
			} catch ( Exception $e ) {
				$response = array (
						"errors" => $e->getMessage () 
				);
			}
			echo Zend_Json::encode ( $response );
			exit ();
		}
		$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 ();
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"mig" => "module_image_gallery_1" 
		), array (
				"mig.module_image_gallery_1_id" => "module_image_gallery_1_id",
				"mig.module_image_gallery_category_1_id" => "module_image_gallery_category_1_id",
				"mig.status" => "status",
				"mig.order" => "order" 
		) )->joinLeft ( array (
				"migd" => "module_image_gallery_detail_1" 
		), "migd.module_image_gallery_1_id = mig.module_image_gallery_1_id AND migd.language_id=" . $active_lang_id, array (
				"migd.module_image_gallery_detail_1_id" => "module_image_gallery_detail_1_id",
				"migd.title" => "title",
				"migd.image_path" => "image_path" 
		) )->where ( "mig.module_image_gallery_category_1_id ='" . $category_id . "'AND mig.customer_id=" . $customer_id )->order ( "mig.order" );
		$response = $mapper->getDbTable ()->fetchAll ( $select )->toArray ();
		foreach ( $response as $key => $thread ) {
			$image_path = $thread ['migd.image_path'];
			$image_uri = "/resource/module-image-gallery-1/thumb/" . $customer_id . "/";
			$ext_image_path = array_pop ( explode ( ".", $image_path ) );
			if ($image_path != "") {
				$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
			}
			$response [$key] ['migd.image_path'] = "<img src='" . $this->view->baseurl ( $image_uri . $image_path ) . "' />";
		}
		$this->view->currentCategory = $category_id;
		$this->view->data = $response;
	}
}