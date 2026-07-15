<?php

namespace App\Http\Controllers;

use App\Http\Models\Task;
use App\Http\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskController extends Controller
{

    // funcion para guardar tareas
    public function store(Request $request){

        $task = $request['task'];

        Task::create([
            'subject' => $task['subject'],
            'desc' => $task['desc'],
            'status' => $task['status'],
            'priority' => $task['priority'],
            'contact' => $task['contact'],
            'account' => $task['account'],
            'finalDate' => $task['finalDate'],
            'subTasks' => $task['subTasks'],
            'isCompleted' => false,
            'createdBy' => session()->get('userLogged')->_id,
            'createdAt' => Carbon::now()->format('Y-m-d h:i:s'),
        ]);

        return response()->json(['message' => 'La tarea ha sido creado con exito'], 200);
    }

    //funcion para actualizar una tarea
    public function update(Request $request){

        $task = $request['task'];

        //Saco la tarea de la bbdd
        $taskToEdit = Task::where('_id', $task['_id'])->first();

        //Actualizo todo

            //Asunto
            $taskToEdit['subject'] = $task['subject'];

            //Descripción
            $taskToEdit['desc'] = $task['desc'];

            //Estado
            $taskToEdit['status'] = $task['status'];

            //Prioridad
            $taskToEdit['priority'] = $task['priority'];

            //fecha final
            $taskToEdit['finalDate'] = $task['finalDate'];

            //Subtareas
            $taskToEdit['subTasks'] = $task['subTasks'];

            //Cuenta
            $taskToEdit['account'] = $task['account'];

            //Contacto
            $taskToEdit['contact'] = $task['contact'];

        $taskToEdit->save();

        return response()->json(['message' => 'La tarea ha sido actualizada correctamente']);
    }

    // funcion para sacar todas las tareas de un usuario
    public function index($id){

        $tasks = Task::where('createdBy', $id)->get();

        foreach ($tasks as $task){
            //Le meto los datos del usuario que lo ha creado
            $task['createdBy'] = User::where('_id', $task['createdBy'])->first();
        }

        return response()->json(['tasks' => $tasks]);
    }

    //funcion para sacar los datos de una tarea en particular
    public function show($id){

        $task = Task::where('_id', $id)->first();

        //Le meto los datos del usuario que lo ha creado
        $task['createdBy'] = User::where('_id', $task['createdBy'])->first();


        //Si tiene cuenta o contacto los meto
        /*if ($task['account'] !== null){
            $task['account'] = Account::where('_id', $task['account'])->first();
        }

        if ($task['contact'] !== null){
            $task['contact'] = Account::where('_id', $task['contact'])->first();
        }*/

        return response()->json(['task' => $task]);
    }

    // funcion para alternar una subtarea completada
    public function toggleSubTask(Request $request){

        $task = $request['task'];
        $subTask = $request['subTask'];
        $subTaskInd = $request['subTaskInd'];

        //Saco la tarea
        $taskToUpdate = Task::where('_id', $task['_id'])->first();

        //Asigo el valor de si esta completado por si se han completado todas las subtareas
        $taskToUpdate['isCompleted'] = $task['isCompleted'];

        //Saco la subtarea marcada y la marco como completada
        $subTasksToModify = $taskToUpdate['subTasks'];

        $subTasksToModify[$subTaskInd] = $subTask;

        $taskToUpdate['subTasks'] = $subTasksToModify;

        //Guardo la tarea
        $taskToUpdate->save();

        return response()->json(['message' => 'La subtarea ha sido marcada como ' . ($subTask['isCompleted'] ? 'marcada' : 'desmarcada') . ' correctamente'], 200);
    }


    // funcion para alternar la tarea
    public function toggleTask(Request $request){

        $task = $request['task'];

        //Saco la tarea
        $taskToUpdate = Task::where('_id', $task['_id'])->first();

        //Marco la tarea como completa/no completa
        $taskToUpdate['isCompleted'] = $task['isCompleted'];


        //Marco cada subtarea como completa/no completa
        $subTasks = $taskToUpdate['subTasks'];

        foreach ($subTasks as &$subTask){
            $subTask['isCompleted'] = $task['isCompleted'];
        }

        $taskToUpdate['subTasks'] = $subTasks;

        $taskToUpdate->save();

        return response()->json(['message' => 'La subtarea ha sido marcada como ' . ($task['isCompleted'] ? 'marcada' : 'desmarcada') . ' correctamente'],200);
    }


    // funcion para borrar una tarea
    public function deleteTask($id){

        Task::destroy($id);

        return response()->json(['message' => 'La tarea ha sido eliminada correctamente'], 200);

    }
}
