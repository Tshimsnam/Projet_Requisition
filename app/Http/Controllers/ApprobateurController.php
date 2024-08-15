<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Approbateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ApprobateurController extends Controller
{
    public function index()
    {
        $users = User::all();
        $approbateurs = Approbateur::query()
        ->orderBy('level','asc')
        ->get();
        return view('approbateurs.index', compact('approbateurs','users'));
    }
    public function create()
    {
       //
    }
    public function store(Request $request)
    {
        $levels = $request->input('level');
        $noms = $request->input('name');
        $fonctions = $request->input('fonction');
        $emails = $request->input('email');
    
        for ($i = 0; $i < count($noms); $i++) {
            $nom = $noms[$i];
            $email = $emails[$i];
    
            // Vérifier si l'approbateur existe dans l'API
            if (!$this->checkApproverInAPI($nom, $email)) {
                return redirect()->back()->with('error', "L'utilisateur $nom avec l'email $email n'a pas été trouvé dans l'API.");
            }
            $approbateurExistant = Approbateur::where('name', $nom)->where('email', $email)->first();
    
            if ($approbateurExistant) {
                if ($approbateurExistant->trashed()) {
                    $approbateurExistant->restore();
                    $approbateurExistant->update([
                        'level' => $levels[$i],
                        'fonction' => $fonctions[$i]
                    ]);
                    return redirect()->back()->with('message', "L'approbateur a été réactivé et mis à jour.");
                } 
                return redirect()->back()->with('error', "L'approbateur le nom ou l'email existe déjà.");
            }
            Approbateur::create([
                'level' => $levels[$i],
                'name' => $nom,
                'fonction' => $fonctions[$i],
                'email' => $email,
            ]);
        }
    
        return redirect()->back()->with('message', "L'approbateur a été ajouté avec succès.");
    }
    
    private function checkApproverInAPI($name, $email)
    {
        $response = Http::get('http://10.143.41.70:8000/promo2/odcapi/?method=getUsers');  
        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                $users = $data['users'];
                foreach ($users as $user) {
                    if ($user['first_name'] . ' ' . $user['last_name'] === $name && $user['email'] === $email) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function edit($id)
    {
        $approbateurs = Approbateur::findOrFail($id);
        return view('approbateurs.index', compact('approbateurs'));
    }
    public function update(Request $request, $id)
    {
        $dataVal = $request->validate([
            'approbateurs' => 'required|array',
            'approbateurs.*.id' => 'nullable|exists:approbateurs,id',
            'approbateurs.*.level' => 'required|integer',
            'approbateurs.*.name' => 'required|string|max:255',
            'approbateurs.*.fonction' => 'required|string|max:255',
            'approbateurs.*.email' => 'required|email|max:255',
        ]);
        foreach ($dataVal['approbateurs'] as $Data) {
            if (isset($Data['id'])) {
                $approbateur = Approbateur::find($Data['id']);
                $approbateur->update($Data);
            } else { 
                Approbateur::create($Data);
            }
        }
        return back();
    }
    public function destroy($id)
    {
        $approbateur = Approbateur::findOrFail($id);
        $approbateur->delete();
                return  redirect()->back()->with('message', 'Approbateur supprimé avec succès');
    }
    public function updateLevels(Request $request)
    {
        $approbateurIds = $request->input('approbateurIds');
        $datas = [];
        foreach ($approbateurIds as $key => $id) {
            $approbateur = Approbateur::where('id', $id)->first();
            $datas[$key]['email'] = $approbateur['email'];
            $datas[$key]['name'] = $approbateur['name'];
            $datas[$key]['fonction'] = $approbateur['fonction'];
            $datas[$key]['level'] = $key + 1;
        }
            Approbateur::truncate();
        foreach ($datas as $data) {
            DB::table('approbateurs')->Insert($data);
            
        }
        $stock = Approbateur::all();
        return response()->json([
            'message' => 'Niveaux des approbateurs mis à jour avec succès.',
            'data' => $stock,
        ]);
    }
}