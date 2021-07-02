<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use InterventionImage;
use App\Http\Requests\UploadImageRequest;

class ShopController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:owners');

        $this->middleware(function($request, $next){
            // dd($request->route()->parameter('shop')); // 文字列
            // dd(Auth::id());  // 数字

            $id = $request->route()->parameter('shop'); // shopのid取得
            if(!is_null($id)){ //null判定
                $shopsOwnerId = Shop::findOrFail($id)->owner->id;
                $shopId = (int)$shopsOwnerId; // キャスト 文字列->数字型
                $ownerId = Auth::id();
                if($shopId !== $ownerId){
                    abort(404); // 404画面表示
                }
            }
            return $next($request);  
        });
        
    }

    public function index()
    {
        // $ownerId = Auth::id();
        $shops = Shop::where('owner_id', Auth::id())->get();

        return view('owner.shops.index',
        compact('shops'));
    }

    public function edit($id)
    {
        $shop = Shop::findOrFail($id);
        // dd(Shop::findOrFail($id));
        return view('owner.shops.edit', compact('shop'));
    }

    public function update(UploadImageRequest $request, $id)
    {

        $request->validate([
            'name' => 'required|string|max:50',
            'information' => 'required|string|information|max:1000',
            'is_selling' => 'required',
        ]);

        $imageFile = $request->image;
        if(!is_null($imageFile) && $imageFile->isValid()){
            $fileNameToStore = Storage::putFile('public/shops', $imageFile); // リサイズなしの場合

            // $filename = ImageService::upload($imageFile, 'shops');

            //リサイズありの場合      (エラーが出るためリサイズなしで処理します)
            // $fileName = uniqid(rand().'_');
            // $extension = $imageFile->extension();
            // $fileNameToStore = $fileName. '.' . $extension;
            // $resizedImage = InterventionImage::make($imageFile)->resize(1920, 1080)->encode();

            // dd($imageFile, $resizedImage);

            // Storage::put('public/shops' . $fileNameToStore, $resizedImage);
        }

        $shop  = Shop::findOrFail($id);
        $shop->name = $request->name;
        $shop->information = $request->information;
        $shop->is_selling = $request->is_selling;

        if(!is_null($imageFile) && $imageFile->isValid()){
            $shop->filename = $fileNameToStore;

        }

        $shop->save();

        return redirect()
        ->route('owner.shops.index')
        ->with(['message' => '店舗情報を更新しました', 
        'status' => 'info']);
    }

}
