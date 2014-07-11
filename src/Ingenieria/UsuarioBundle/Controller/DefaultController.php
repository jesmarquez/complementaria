<?php

namespace Ingenieria\UsuarioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Ingenieria\UsuarioBundle\Entity\Director;
use Ingenieria\UsuarioBundle\Entity\Usuario;
use Ingenieria\UsuarioBundle\Entity\Document;
use Ingenieria\UsuarioBundle\Form\Type\DirectorType;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
   public function indexAction()
    {
		
		if ($this->get('security.context')->isGranted('ROLE_COORDINADOR')) {
        	return $this->redirect($this->generateUrl('cituao_coord_homepage'));
    	}
		else{
			if ($this->get('security.context')->isGranted('ROLE_PRACTICANTE')) {
				return $this->redirect($this->generateUrl('cituao_practicante_homepage'));
			}else{
				if ($this->get('security.context')->isGranted('ROLE_PROFESOR')) {
					return $this->redirect($this->generateUrl('ingenieria_profesor_homepage'));
				}else {
				if ($this->get('security.context')->isGranted('ROLE_DIRECTOR')) {
					return $this->redirect($this->generateUrl('ingenieria_director_homepage'));
				}else {
					if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
						return $this->redirect($this->generateUrl('usuario_adm_homepage'));
				}
			}
		}			
		}
		
	}
     return $this->render('IngenieriaUsuarioBundle:Default:portal.html.twig', array("error"=>array("message"=>"")));
    }

	//*******************************************************
	//Proceso de autenticación 
	//*******************************************************
    public function loginAction()
    {
        $peticion = $this->getRequest();
        $sesion = $peticion->getSession();

        $error = $peticion->attributes->get(
            SecurityContext::AUTHENTICATION_ERROR,
            $sesion->get(SecurityContext::AUTHENTICATION_ERROR)
        );
        return $this->render(
            'IngenieriaUsuarioBundle:Default:portal.html.twig',
            array(
                // last username entered by the user
                'last_username' => $sesion->get(SecurityContext::LAST_USERNAME),
                'error'         => $error
            )
        );
    }
	
	//******************************************************
	// Home del administrador de la aplicacion
	//******************************************************
	public function homeAdmAction(){
	
		$repository = $this->getDoctrine()->getRepository('IngenieriaUsuarioBundle:Director');
		$directores = $repository->findAll();
		
		
		if (!$directores) {
			//throw $this->createNotFoundException('ERR_NO_HAY_PROGRAMA');
			$msgerr = array('id'=>1, 'descripcion' => 'No hay directores registrados en el sistema');
		}else{
			$msgerr = array('id'=>0, 'descripcion' => 'Ok');
		}
		return $this->render('IngenieriaUsuarioBundle:Default:directores.html.twig',  array('listaDirectores' => $directores, 'msgerr' => $msgerr));
	}
	
	/********************************************************/
	// Registra y modifica un director academico
	/********************************************************/		
	public function registrarDirectorAction(){

		$peticion = $this->getRequest();
		$em = $this->getDoctrine()->getManager();

		$director = new Director();

		$formulario = $this->createForm(new DirectorType(), $director);
		$formulario->handleRequest($peticion);

		if ($formulario->isValid()) {
			//validamos que no existe el director
			$repository = $this->getDoctrine()->getRepository('IngenieriaUsuarioBundle:Director');
			$d = $repository->findOneBy(array('ci' => $director->getCi()));

			if ($d != NULL){
				throw $this->createNotFoundException('ERR_DIRECTOR_REGISTRADO');
			}

		   // Completar las propiedades que el usuario no rellena en el formulario

			$em->persist($director);

			//los roles fueron cargados de forma manual en la base de datos
			//buscamos una instancia role tipo DIRECTOR 
			
			$codigo = 1; //codigo ID q corresponde al director
			$repository = $this->getDoctrine()->getRepository('IngenieriaUsuarioBundle:Role');
			$role = $repository->findOneBy(array('id' => $codigo));

			if ($role == NULL){
				throw $this->createNotFoundException('ERR_ROLE_NO_ENCONTRADO');
			}
			$usuario = new Usuario();
			//cargamos todos los atributos al usuario
			$usuario->setUsername($director->getCi());
			$usuario->setPassword($formulario->get('password')->getData());
			$usuario->setSalt(md5(time()));
			$usuario->addRole($role);  //cargamos el rol al coordinador

			//codificamos el password			
			$encoder = $this->get('security.encoder_factory')->getEncoder($usuario);
			$passwordCodificado = $encoder->encodePassword($usuario->getPassword(), $usuario->getSalt());
			$usuario->setPassword($passwordCodificado);
			$em->persist($usuario);
			

			$em->flush();
			return $this->redirect($this->generateUrl('usuario_adm_homepage'));
		}

		return $this->render('IngenieriaUsuarioBundle:Default:registrardirector.html.twig', array(
			'formulario' => $formulario->createView()
			));		

	}		
	
		//******************************************************
	// Home del administrador de la aplicacion
	//******************************************************
	public function estudiantesAction(){
		$document = new Document();
		$form = $this->createFormBuilder($document)
		->add('file')
		->add('name')
		->getForm();
	
		$repository = $this->getDoctrine()->getRepository('IngenieriaEstudianteBundle:Estudiante');
		$estudiantes = $repository->findAll();
		
		
		if (!$estudiantes) {
			//throw $this->createNotFoundException('ERR_NO_HAY_PROGRAMA');
			$msgerr = array('id'=>1, 'descripcion' => 'No hay estudiantes registrados en el sistema');
		}else{
			$msgerr = array('id'=>0, 'descripcion' => 'Ok');
		}
		return $this->render('IngenieriaUsuarioBundle:Default:estudiantes.html.twig',  array('listaEstudiantes' => $estudiantes,  'form' => $form->createView() , 'msgerr' => $msgerr));
	}
	
	//********************************************************************
	// SUBIR ESTUDIANTES A PARTIR DE UN CSV
	//********************************************************************
	public function subirEstudiantesAction(Request $request){
			
		$document = new Document();
		$form = $this->createFormBuilder($document)
		->add('file')
		->add('name')
		->getForm();

		$form->handleRequest($request);

		if ($form->isValid()) {
			//levantar servicios de doctrine base de datos
			$em = $this->getDoctrine()->getManager();

			//se copia el archivo al directorio del servidor			
			$document->upload();

			$em->persist($document);
		    //$em->flush();

			$archivo= $document->getAbsolutePath();		
			//bajamos el archivo a una matriz para procesar registro a registro y bajarlo a base de datos		    
			$filas = file($archivo);
			$i=0;
			$numero_fila= count($filas);	

			//para buscar si ya se encuentra en la base de datos
			$repository = $this->getDoctrine()->getRepository('IngenieriaEstudianteBundle:Estudiante');

			$nohay = true;			
			//procesamos la matriz separando los campos por medio del separador putno y coma
			while($i <= $numero_fila -1){
				$row = $filas[$i];
				$sql = explode(";",$row);

				$e = $repository->findOneBy(array('ci' => $sql[3]));
				//Si esta en la base de datos lo ignoramos				
				if ($e != NULL){
					$i++;						
					continue;
				}

				$listaEstudiantes[$i] =  array("codigo"=> $sql[0], "apellidos"=>$sql[1], "nombres"=>$sql[2], "ci" => $sql[3], 	
					"fecha" => $sql[4], "emailInstitucional" => $sql[5] );
				$i++;
				$nohay = false;
			}
			
			/*
			if (!$nohay){
				
				//los roles fueron cargados de forma manual en la base de datos
				//buscamos una instancia role tipo practicante 
				$codigo = 2; //1 corresponde a practicantes		
				$repository = $this->getDoctrine()->getRepository('CituaoUsuarioBundle:Role');
				$role = $repository->findOneBy(array('id' => $codigo));

					//buscamos el programa
				$user = $this->get('security.context')->getToken()->getUser();
				$coordinador =  $user->getUsername();
				$repository = $this->getDoctrine()->getRepository('CituaoUsuarioBundle:Programa');
				$programa = $repository->findOneByCoordinador($coordinador);
						
				$repository = $this->getDoctrine()->getRepository('CituaoCoordBundle:Area');
				$area = $repository->findOneById($id_area);
				
				//buscamos los periodos y el periodo actual
				$repository = $this->getDoctrine()->getRepository('CituaoUsuarioBundle:Periodo');
				$query = $repository->createQueryBuilder('p')
						->orderBy('p.id','DESC')
						->getQuery();
				$periodos = $query->getResult();
				foreach ($periodos as $periodoActual){
					break;
				}

				*/
				
				//procesamos la matriz  fila a fila creando practicantes y usuarios
				$i=0;				
				$sad = "";	
		/*
				while($i <= $numero_fila -1){
					//creamos una instancia Practicante para descargar datos del CSV y guardar en la base de datos
					$practicante = new Practicante();
					//creamos una instancia de usuario para darle entrada a los practicantes como usuarios en el sistema
					$usuario = new Usuario();

					//viene del archivo .csv	
					//cargamos todos los atributos al practicante
					$practicante->setCodigo($listaEstudiantes[$i]['codigo']);
					$practicante->setNombres($listaEstudiantes[$i]['nombres']);
					$practicante->setApellidos($listaEstudiantes[$i]['apellidos']);
					$practicante->setEmailInstitucional($listaEstudiantes[$i]['emailInstitucional']);
					$practicante->setCi($listaEstudiantes[$i]['ci']);
					$practicante->setPrograma($programa);
					$practicante->setPeriodo($periodoActual);
					
					//cargamos todos los atributos al usuario
					$usuario->setUsername($listaEstudiantes[$i]['codigo']) ;
					$usuario->setPassword($listaEstudiantes[$i]['ci']);
					$usuario->setSalt(md5(time()));
					$usuario->addRole($role); //cargamos el rol al coordinador
					$usuario->setIsActive(false); //no puede tener acceso 

					$encoder = $this->get('security.encoder_factory')->getEncoder($usuario);
		            $passwordCodificado = $encoder->encodePassword($usuario->getPassword(), $usuario->getSalt());
					$usuario->setPassword($passwordCodificado);


					 $em->persist($usuario);

					 //convertimos la fecha de matricula a un objeto Date				
					$fecha = $listaEstudiantes[$i]['fecha'];
					$separa = explode("/",$fecha);
					$dia = $separa[0];
					$mes = $separa[1];
					$ano = $separa[2];

					$f = new \DateTime();
					$f->setDate($ano,$mes,$dia);

					$practicante->setFechaMatriculacion($f);

					//cargamos los demas datos
					//$practicante->setTelefonoMovil($sad);

					$practicante->setArea($area);


					$practicante->setEstado(false);

					$practicante->setPath('defaultPicture.png');
					$em->persist($practicante);
					$em->flush();
					$i++;
				}
			}
		*/
			$msgerr = array('id'=>'0', 'descripcion'=>' ');
			return $this->render('IngenieriaUsuarioBundle:Default:subirestudiantes.html.twig', array('listaEstudiantes' => $listaEstudiantes , 'msgerr' => $msgerr));
			
			//return $this->redirect($this->generateUrl('cituao_coord_practicantes'));
		} 
		
		$msgerr = array('id'=>'0', 'descripcion'=>' ');
		/*
				//buscamos el programa
		$user = $this->get('security.context')->getToken()->getUser();
		$coordinador =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('CituaoUsuarioBundle:Programa');
		$programa = $repository->findOneByCoordinador($coordinador);
		*/
		
		return $this->render('IngenieriaUsuarioBundle:Default:subirestudiantes.html.twig', array('listaEstudiantes' => $listaEstudiantes , 'msgerr' => $msgerr));
	}
}
