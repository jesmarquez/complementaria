<?php

namespace Ingenieria\ProfesorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ingenieria\EstudianteBundle\Entity\Estudiante;
use Ingenieria\ProfesorBundle\Entity\Actividad;
use Ingenieria\ProfesorBundle\Entity\Profesor;
use Ingenieria\ProfesorBundle\Form\Type\ActividadType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class DefaultController extends Controller
{
    public function indexAction()
    {
		//localizamos al profesor
		$user = $this->get('security.context')->getToken()->getUser();
		$ci =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Profesor');
		$profesor = $repository->findOneBy(array('ci' => $ci));

		//buscamos si el profesor o tutor tiene un grupo asignado
		$repository = $this->getDoctrine()->getRepository('IngenieriaDirectorBundle:Grupo');
		$grupo = $repository->findOneBy(array('tutor' => $profesor->getId()));

		//Sino tiene grupo mensaje de advertencia
		if (!$grupo) {
			$msgerr = array('descripcion'=>'Aún no tiene grupo asignado!','id'=>'1');
			$estudiantes = array();
		}else{
			$estudiantes = $grupo->getEstudiantes();

			if (!$estudiantes) {
				$msgerr = array('descripcion'=>'No hay estudiantes registrados!','id'=>'1');
			}else{
				$msgerr = array('descripcion'=>'','id'=>'0');
			}
		}
		
		return $this->render('IngenieriaProfesorBundle:Default:estudiantes.html.twig', array('listaEstudiantes' => $estudiantes, 'msgerr' => $msgerr));
    }

	//********************************************************
	// Muestra un listado de estudiantes
	//******************************************************** 	
	public function estudiantesAction(){
		//localizamos al profesor
		$user = $this->get('security.context')->getToken()->getUser();
		$ci =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Profesor');
		$profesor = $repository->findOneBy(array('ci' => $ci));

		//buscamos si el profesor o tutor tiene un grupo asignado
		$repository = $this->getDoctrine()->getRepository('IngenieriaDirectorBundle:Grupo');
		$grupo = $repository->findOneBy(array('tutor' => $profesor->getId()));
		
		//Sino tiene grupo mensaje de advertencia
		if (!$grupo) {
			$msgerr = array('descripcion'=>'Aun no tiene grupo asignado!','id'=>'1');
		}else{
			$estudiantes = $grupo->getEstudiantes();

			if (!$estudiantes) {
				$msgerr = array('descripcion'=>'No hay estudiantes registrados!','id'=>'1');
			}else{
				$msgerr = array('descripcion'=>'','id'=>'0');
			}
		}
		
		return $this->render('IngenieriaProfesorBundle:Default:estudiantes.html.twig', array('listaEstudiantes' => $estudiantes, 'msgerr' => $msgerr));
	}

	//********************************************************
	// Muestra un listado de actividades del profesor
	//******************************************************** 	
	public function actividadesAction(){
		//localizamos al profesor
		$user = $this->get('security.context')->getToken()->getUser();
		$ci =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Profesor');
		$profesor = $repository->findOneBy(array('ci' => $ci));

		//$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Actividad');
		//$listaActividades = $repository->findAll();
		
		$listaActividades =  $profesor->getActividades();
		if ( $listaActividades->count() == 0) {
			$msgerr = array('descripcion'=>'No hay actividades registradas!','id'=>'1');
		}else{
			$msgerr = array('descripcion'=>'','id'=>'0');
		}
		/*
				//buscamos el programa
		$user = $this->get('security.context')->getToken()->getUser();
		$coordinador =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('CituaoUsuarioBundle:Programa');
		$programa = $repository->findOneByCoordinador($coordinador);
		*/
		
		return $this->render('IngenieriaProfesorBundle:Default:actividades.html.twig', array('listaActividades' => $listaActividades, 'msgerr' => $msgerr));
	}

	/********************************************************/
	// Registra una actividad
	/********************************************************/		
	public function registrarActividadAction(){
		$user = $this->get('security.context')->getToken()->getUser();
		$ci =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Profesor');
		$profesor = $repository->findOneBy(array('ci' => $ci));


		$peticion = $this->getRequest();
		$em = $this->getDoctrine()->getManager();

		$actividad = new Actividad();

		$formulario = $this->createForm(new ActividadType(), $actividad);
		$formulario->handleRequest($peticion);

		if ($formulario->isValid()) {
			$actividad->setProfesor($profesor);
			$em->persist($actividad);

			$em->flush();
			return $this->redirect($this->generateUrl('ingenieria_profesor_actividades'));
		}

		return $this->render('IngenieriaProfesorBundle:Default:registraractividades.html.twig', array(
			'formulario' => $formulario->createView()
			));		
	}	
	
	//**********************************************************
	//Muestra la informacion de la actividad complementaria
	//**********************************************************
	public function actividadAction($id){
		$peticion = $this->getRequest();
		$em = $this->getDoctrine()->getManager();

		// buscamos el ID del asesor academico
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Actividad');
		$actividad = $repository->findOneBy(array('id' => $id));
		
		$formulario = $this->createForm(new ActividadType(), $actividad);
		$formulario->handleRequest($peticion);

		if ($formulario->isValid()) {
			$em->persist($actividad);
			$em->flush();

			return $this->redirect($this->generateUrl('ingenieria_profesor_homepage'));
		}
		/*
		//buscamos el programa
		$user = $this->get('security.context')->getToken()->getUser();
		$coordinador =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('CituaoUsuarioBundle:Programa');
		$programa = $repository->findOneByCoordinador($coordinador);
		*/
		
		return $this->render('IngenieriaProfesorBundle:Default:actividad.html.twig', array('formulario' => $formulario->createView(), 'actividad' => $actividad ));
	}
	
	//*****************************************************************
	//Muestra el cronograma de actividades de un estudiante
	//*****************************************************************
	
	public function cronogramaAction($id){
		$repository = $this->getDoctrine()->getRepository('IngenieriaEstudianteBundle:Estudiante');
		$estudiante = $repository->findOneBy(array('id' => $id));
	
		$cronograma = null;
		$cronograma = $estudiante->getActividades();
		
		if ( $cronograma->count()  == 0 ) {
			$msgerr = array('descripcion'=>'¡No ha subido el cronograma de actividades!','id'=>'1');
		}else{
			$msgerr = array('descripcion'=>'','id'=>'0');
		}
		
		return $this->render('IngenieriaProfesorBundle:Default:cronograma.html.twig', array('estudiante' => $estudiante, 'cronograma' => $cronograma, 'msgerr' => $msgerr ));
	}

	//****************************************************************
	// Coloca true el campo de aprobado en el estudiante
	//****************************************************************
	public function aprobarAction($id){
		
		$repository = $this->getDoctrine()->getRepository('IngenieriaEstudianteBundle:Estudiante');
		$estudiante = $repository->findOneBy(array('id' => $id));
	
		$em = $this->getDoctrine()->getManager();

		$estudiante->setAprobadoCronograma(true);
		$em->persist($estudiante);		
		$em->flush();

		$cronograma = null;
		$cronograma = $estudiante->getActividades();

		if ( $cronograma->count()  == 0 ) {
			$msgerr = array('descripcion'=>'¡No ha subido el cronograma de actividades!','id'=>'1');
		}else{
			$msgerr = array('descripcion'=>'','id'=>'0');
		}
		
		return $this->render('IngenieriaProfesorBundle:Default:cronograma.html.twig', array('estudiante' => $estudiante, 'cronograma' => $cronograma, 'msgerr' => $msgerr ));
	}

	//****************************************************************
	// Coloca true el campo de aprobado en el estudiante
	//****************************************************************
	public function rechazarAction($id){
		
		$repository = $this->getDoctrine()->getRepository('IngenieriaEstudianteBundle:Estudiante');
		$estudiante = $repository->findOneBy(array('id' => $id));
	
		$em = $this->getDoctrine()->getManager();

		$estudiante->setRechazadoCronograma(true);
		$em->persist($estudiante);		
		$em->flush();

		$cronograma = null;
		$cronograma = $estudiante->getActividades();

		if ( $cronograma->count()  == 0 ) {
			$msgerr = array('descripcion'=>'¡No ha subido el cronograma de actividades!','id'=>'1');
		}else{
			$msgerr = array('descripcion'=>'','id'=>'0');
		}
		
		return $this->render('IngenieriaProfesorBundle:Default:cronograma.html.twig', array('estudiante' => $estudiante, 'cronograma' => $cronograma, 'msgerr' => $msgerr ));
	}

	/**********************************************************************************/
	// Muestra bitacora de trabajo semanal del estudiante
	/**********************************************************************************/		
	public function bitacoraAction($id){
    	$repository = $this->getDoctrine()->getRepository('IngenieriaEstudianteBundle:Estudiante');
    	$estudiante = $repository->findOneBy(array('id' => $id));
		
		$bitacora = $estudiante->getBitacora();
		
		if (!$bitacora) {
			$msgerr = array('descripcion'=>'No hay actividades registradas!','id'=>'1');
		}else{
			$msgerr = array('descripcion'=>'','id'=>'0');
		}
		
		return $this->render('IngenieriaProfesorBundle:Default:bitacora.html.twig', array('estudiante' => $estudiante, 'bitacora'=>$bitacora, 'msgerr' => $msgerr));
	}

	/**********************************************************************/
	// Registra una actividad en el cronograma el paso de datos es por ajax
	/**********************************************************************/		
	public function verificarActividadAction(){

		$em = $this->getDoctrine()->getManager();
		//recuperamos el id de la actividad
		$request = $this->getRequest();
		$id_actividad = $request->request->get('id');

		//buscamos la actividad en la bitacora
    	$repository = $this->getDoctrine()->getRepository('IngenieriaEstudianteBundle:Bitacora');
    	$actividad = $repository->findOneBy(array('id' => $id_actividad));
	

		if ($actividad->getVerificado()){
			$actividad->setVerificado(false);
		}
		else {
			$actividad->setVerificado(true);
		}

		$em->persist($actividad);
		$em->flush();
		
		$op="a";
		$r = array("operacion" => $op);
		$serializer = new Serializer(array(new GetSetMethodNormalizer()), array('json' => new 
			JsonEncoder()));
		$json = $serializer->serialize($r, 'json');
		
			
		return new Response($json);
		
	}	

	//********************************************************
	// Muestra un listado de grupos
	//******************************************************** 	
	public function gruposAction(){
		//localizamos al profesor
		$user = $this->get('security.context')->getToken()->getUser();
		$ci =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Profesor');
		$profesor = $repository->findOneBy(array('ci' => $ci));

		$listaGrupos = $profesor->getGrupos();
		
		//Sino tiene grupo mensaje de advertencia
		if (!$listaGrupos) {
			$msgerr = array('descripcion'=>'No tienes grupos asignados!','id'=>'1');
		} 
		else {
			$msgerr = array('descripcion'=>'','id'=>'0');
		}

		
		return $this->render('IngenieriaProfesorBundle:Default:grupos.html.twig', array('listaGrupos' => $listaGrupos, 'msgerr' => $msgerr));
	}

	//********************************************************
	// Muestra un listado de grupos
	//******************************************************** 	
	public function estudiantesCursoAction($id){
		//localizamos al profesor
		$user = $this->get('security.context')->getToken()->getUser();
		$ci =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Profesor');
		$profesor = $repository->findOneBy(array('ci' => $ci));

		$grupos = $profesor->getGrupos();
		
		foreach ($grupos as $g){
			if ($g->getId() == $id) break;		
		}

		$msgerr = array('descripcion'=>'','id'=>'0');

		$estudiantes = $g->getEstudiantes();
		return $this->render('IngenieriaProfesorBundle:Default:estudiantes.html.twig', array('listaEstudiantes' => $estudiantes, 'msgerr' => $msgerr));
	}

	//********************************************************
	// Muestra un listado de subgrupos
	//******************************************************** 	
	public function subGruposAction($id){
		//localizamos al profesor
		$user = $this->get('security.context')->getToken()->getUser();
		$ci =  $user->getUsername();
		$repository = $this->getDoctrine()->getRepository('IngenieriaProfesorBundle:Profesor');
		$profesor = $repository->findOneBy(array('ci' => $ci));

		$grupos = $profesor->getGrupos();
		
		foreach ($grupos as $g){
			if ($g->getId() == $id) break;		
		}

		$subgrupos = $g->getSubgrupos();

		//Sino tiene grupo mensaje de advertencia
		if (!$subgrupos) {
			$msgerr = array('descripcion'=>'No tienes subgrupos definidos!','id'=>'1');
		} 
		else {
			$msgerr = array('descripcion'=>'','id'=>'0');
		}

		
		return $this->render('IngenieriaProfesorBundle:Default:subgrupos.html.twig', array('listaSubgrupos' => $subgrupos, 'msgerr' => $msgerr));
	}
	
}
