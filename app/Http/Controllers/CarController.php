<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Constants\ApiEndpoints;
use Illuminate\Support\Facades\Http; // Thư viện HTTP client của Laravel
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class CarController extends Controller
{
    private $search = false;
    private const PAGE_SIZE = 5;

    // Phương thức để hiển thị danh sách xe với phân trang
    public function carManagement($page = 1)
    {
        $response = Http::get("http://localhost:8080/api/car/page", [
            'pageSize' => self::PAGE_SIZE,
            'pageNo' => $page - 1,
        ]);

        $cars = $response['data']['items'];
        $totalPages = $response['data']['totalPage'];
        $totalElements = $response['data']['totalElements'];

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        // Lưu trữ thông tin phân trang vào session
        session()->put('currentPage', $page);
        session()->put('pageSize', self::PAGE_SIZE);
        session()->put('totalPages', $totalPages);
        session()->put('totalElements', $totalElements);

        // Lấy thông báo từ session và xóa nó
        $message = session()->get('message');
        session()->forget('message');

        $carTypes = CarTypeController::getAllCarType();
        $carTypeSearch = 'All';
        $status = 'All';

        $search = false;
        return view('Admin.Pages.car', [
            'cars' => $cars,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'pageSize' => self::PAGE_SIZE,
            'message' => $message,
            'carTypes' => $carTypes,
            'carTypeSearch' => $carTypeSearch,
            'status' => $status,
            'search' => $search
        ]);
    }

    public static function getAllCar()
    {
        $cars = ApiController::getData(ApiEndpoints::API_CAR_LIST);
        return $cars;
    }

    // Phương thức tìm xe theo ID
    public static function  findCarById($carId)
    {
        $response = ApiController::getData(ApiEndpoints::API_CAR_SEARCH_BY_ID . $carId);
        return $response;
    }

    // Phương thức thêm xe mới
    public function addCar(Request $request)
    {
        $carTypeId = $request->input('carType');
        $carType = CarTypeController::findCarTypeById($carTypeId);

        $carRequest = [
            'carType' => $carType,
            'licensePlate' => $request->input('carLicensePlate'),
            'numberOfSeats' => intval($request->input('seatNumber')),
            'image' => '',
            'status' => $request->input('carStatus')
        ];

        $apiResponse = ApiController::postData(ApiEndpoints::API_CAR_ADD, $carRequest);

        // Kiểm tra xem $apiResponse có phải là một đối tượng JsonResponse không
        if ($apiResponse instanceof \Illuminate\Http\JsonResponse) {
            $responseData = $apiResponse->getData(true); // Chuyển đổi JsonResponse thành mảng

            // Xử lý phản hồi từ API
            if (isset($responseData['success']) && $responseData['success'] == true && $responseData['code'] == 200) {
                // Nếu thành công, lưu thông báo thành công vào session và điều hướng đến trang mới
                $message = "Thêm xe thành công";
                session()->put('message', $message);

                // Cập nhật các giá trị phân trang từ session
                $totalPages = session()->get('totalPages', 1);
                $totalElements = session()->get('totalElements', 0);
                $pageSize = session()->get('pageSize', 5);

                // Tính số trang mới sau khi thêm loại xe
                $totalPages = ($totalElements % $pageSize == 0) ? $totalPages + 1 : $totalPages;

                // Điều hướng đến trang mới
                return redirect()->route('car', ['page' => $totalPages]);
            } else {
                // Nếu không thành công, lưu thông báo lỗi vào session và trả về phản hồi lỗi
                $message = $responseData['message'] ?? 'Có lỗi xảy ra khi thêm xe';
                session()->put('message', $message);

                // Trả về phản hồi lỗi (có thể điều chỉnh tùy theo yêu cầu)
                return redirect()->back()->with('error', $message);
            }
        } else {
            // Nếu không phải JsonResponse, xử lý trường hợp lỗi hoặc thông báo không chính xác
            $message = 'Có lỗi xảy ra khi thêm xe';
            session()->put('message', $message);
            return redirect()->back()->with('error', $message);
        }
    }

    // Phương thức cập nhật xe
    public function updateCar(Request $request, $carId)
    {
        $carTypeId = $request->input('carType');
        $carType = CarTypeController::findCarTypeById($carTypeId);

        $carRequest = [
            'carType' => $carType,
            'licensePlate' => $request->input('carLicensePlate'),
            'numberOfSeats' => intval($request->input('seatNumber')),
            'image' => '',
            'status' => $request->input('carStatus')
        ];

        $carId = $request->input('carId');
        $apiResponse = ApiController::putData(ApiEndpoints::API_CAR_UPDATE . $carId, $carRequest);

        // Kiểm tra xem $apiResponse có phải là một đối tượng JsonResponse không
        if ($apiResponse instanceof \Illuminate\Http\JsonResponse) {
            $responseData = $apiResponse->getData(true); // Chuyển đổi JsonResponse thành mảng

            // Xử lý phản hồi từ API
            if (isset($responseData['success']) && $responseData['success'] == true && $responseData['code'] == 200) {
                // Nếu thành công, lưu thông báo thành công vào session và điều hướng đến trang mới
                $message = "Cập nhật xe thành công";
                session()->put('message', $message);

                // Cập nhật các giá trị phân trang từ session
                $currentPage = session()->get('currentPage', 1);
                $totalPages = session()->get('totalPages', 1);

                // Điều hướng đến trang hiện tại
                return redirect()->route('car', ['page' => $currentPage]);
            } else {
                // Nếu không thành công, lưu thông báo lỗi vào session và trả về phản hồi lỗi
                $message = $responseData['message'] ?? 'Có lỗi xảy ra khi cập nhật xe';
                session()->put('message', $message);

                // Trả về phản hồi lỗi (có thể điều chỉnh tùy theo yêu cầu)
                return redirect()->back()->with('error', $message);
            }
        } else {
            // Nếu không phải JsonResponse, xử lý trường hợp lỗi hoặc thông báo không chính xác
            $message = 'Có lỗi xảy ra khi cập nhật xe';
            session()->put('message', $message);
            return redirect()->back()->with('error', $message);
        }
    }

    // Phương thức xóa xe
    public function deleteCar($carId)
    {
        $carResponse = ApiController::deleteData(ApiEndpoints::API_CAR_DELETE . $carId);

        $currentPage = session()->get('currentPage', 1);
        $totalPages = session()->get('totalPages', 1);
        $totalElements = session()->get('totalElements', 0);
        $pageSize = session()->get('pageSize', 5);

        $currentPage = ($totalElements % $pageSize == 1 && $currentPage == $totalPages) ? $currentPage - 1 : $currentPage;

        return redirect()->route('car', ['page' => $currentPage]);
    }

    public function search(Request $request, $page = 1)
    {
        // Lấy tên loại xe từ yêu cầu
        $licensePlate = $request->input('licensePlate');
        $carTypeSearch = $request->input('carTypeSearch');
        $numberOfSeats = $request->input('numberOfSeats');
        $status = $request->input('status');
        
  
        if ($licensePlate == '' &&  $carTypeSearch == 'All' &&   $numberOfSeats == '' &&  $status == 'All')
            return redirect()->route('car', $page = 1);

        // Tạo đường dẫn API dựa trên các tham số
        $apiUrl = 'http://localhost:8080/api/car/search?';

        // Thêm các tham số vào URL nếu có giá trị
        if ($licensePlate != '') {
            $apiUrl .= 'licensePlate=' . urlencode($licensePlate) . '&';
        }
        if ($carTypeSearch != 'All') {
            $apiUrl .= 'carTypeName=' . urlencode($carTypeSearch) . '&';
        }
        if ($numberOfSeats != '') {
            $apiUrl .= 'numberOfSeats=' . $numberOfSeats . '&';
        }

        if ($status != 'All') {
            $apiUrl .= 'status=' . urlencode($status) . '&';
        }

        // Thêm phân trang (số trang và kích thước trang)
        $pageNo = 0; // có thể lấy từ request hoặc gán mặc định
        $pageSize = 5; // số lượng mục mỗi trang
        $apiUrl .= 'pageNo=' . $pageNo . '&pageSize=' . $pageSize;

        // Bỏ dấu '&' cuối cùng (nếu có)
        $apiUrl = rtrim($apiUrl, '&');

        // Gọi API để lấy dữ liệu loại xe theo tên với phân trang
        $apiResponse = ApiController::getData($apiUrl);

        $cars = $apiResponse['cars'];
        $totalPages = $apiResponse['totalPages'];

        // Lấy thông báo từ session và xóa nó
        $message = session()->get('message');
        session()->forget('message'); // Xóa thông báo khỏi session

        $carTypes = CarTypeController::getAllCarType();

        $search = true;
        return view('Admin.Pages.car', [
            'carTypes' => $carTypes,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'pageSize' => self::PAGE_SIZE,
            'message' => $message,
            'licensePlate' => $licensePlate,
            'carTypeSearch' => $carTypeSearch,
            'numberOfSeats' => $numberOfSeats,
            'status' => $status,
            'cars' => $cars,
            'search' => $search
        ]);
    }
}
