<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\Provinces;
use App\Models\Regencies;
use Config\App;

class Desa extends ResourceController
{
    protected $provinces = 'App\Models\Provinces';
    protected $regencies = 'App\Models\Regencies';
    protected $district = 'App\Models\District';
    protected $villages = 'App\Models\Villages';
    protected $format = 'json';

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function desa()
    {
        $villagesModel = new $this->villages;

        $data = [
            'message' => 'berhasil',
            'nama desa' => $villagesModel->findAll()
        ];

        return $this->respond($data, 200);
    }

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function detailDesa($id)
    {
        $villagesModel = new $this->villages;
        $districtModel = new $this->district;
        $regenciesModel = new $this->regencies;
        $provincesModel = new $this->provinces;

        // data
        $desa = $villagesModel->select('name')->where('id', $id)->first();
        $dataDesa = $desa ? $desa['name'] : null;

        if ($dataDesa) {

            $kecamatan = $districtModel->select('name')->where('id', ($villagesModel->select('district_id')->where('id', $id)->first()['district_id']))->first();
            $dataKecamatan = $kecamatan ? $kecamatan['name'] : null;

            $kabupaten = $regenciesModel->select('name')->where('id', ($districtModel->select('regency_id')->where('id', ($villagesModel->select('district_id')->where('id', $id)->first()['district_id'])))->first()['regency_id'])->first();
            $dataKabupaten = $kabupaten ? $kabupaten['name'] : null;

            $provinsi = $provincesModel->select('name')->where('id', ($regenciesModel->select('province_id')->where('id', ($districtModel->select('regency_id')->where('id', ($villagesModel->select('district_id')->where('id', $id)->first()['district_id'])))->first()['regency_id']))->first()['province_id'])->first();
            $dataProvinsi = $provinsi ? $provinsi['name'] : null;

            $data = [
                'desa' => $dataDesa,
                'kecamatan' => $dataKecamatan,
                'kabupaten' => $dataKabupaten,
                'provinsi' => $dataProvinsi,
            ];

            return $this->respond($data, 200);
        } else {
            $errors = "Data desa tidak ditemukan";

            return $this->fail($errors, 400);
        }
    }

    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return ResponseInterface
     */
    public function create()
    {
        $validation = \Config\Services::validation();

        $villagesModel = new $this->villages;
        $districtModel = new $this->district;
        $regenciesModel = new $this->regencies;
        $provincesModel = new $this->provinces;

        $provinsi = esc($this->request->getVar('provinsi'));
        $kabupaten = esc($this->request->getVar('kabupaten'));
        $kecamatan = esc($this->request->getVar('kecamatan'));
        $desa = esc($this->request->getVar('desa'));


        $data = [
            'provinsi' => $provinsi,
            'kabupaten' => $kabupaten,
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ];

        $rules = [
            'provinsi' => [
                'label' => 'provinsi',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
            'kabupaten' => [
                'label' => 'kabupaten',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
            'kecamatan' => [
                'label' => 'kecamatan',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
            'desa' => [
                'label' => 'desa',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
        ];

        $validation->setRules($rules);

        if (!$validation->run($data)) {
            $result = $validation->getErrors();

            return $this->fail($result, 400);
        }

        if ($validation->run($data)) {
            $kab = '';
            $kot = '';
            if (stripos($kabupaten, 'kabupaten') === false && stripos($kabupaten, 'kota') === false) {
                $kab = 'kabupaten ' . $kabupaten;
                $kot = 'kota ' . $kabupaten;
            }
            $error = [];

            $val_provinsi = $provincesModel->select()->where('name', $provinsi)->first();

            if ($val_provinsi) {

                $val_kabupaten = $regenciesModel->select()->where('province_id', $val_provinsi['id'])->where('name', $kabupaten)->first();

                if (!$val_kabupaten) {
                    if ($kab) {
                        $val_kabupaten = $regenciesModel->select()->where('province_id', $val_provinsi['id'])->where('name', $kab)->first();
                    }
                    if (!$val_kabupaten) {
                        if ($kot) {
                            $val_kabupaten = $regenciesModel->select()->where('province_id', $val_provinsi['id'])->where('name', $kot)->first();
                        }
                    }
                }

                if ($val_kabupaten) {
                    $val_kecamatan = $districtModel->select()->where('regency_id', $val_kabupaten['id'])->where('name', $kecamatan)->first();

                    if ($val_kecamatan) {
                        $val_desa = $villagesModel->select()->where('district_id', $val_kecamatan['id'])->where('name', $desa)->first();

                        if ($val_desa) {
                            $error['desa'] = "nama desa telah terdaftar";
                        }
                    } else {
                        $error['kecamatan'] = "Nama kecamatan tidak terdaftar di kabupaten tersebut";
                    }
                } else {
                    $error['kabupaten'] = "Nama kabupaten tidak terdaftar di provinsi tersebut";
                }
            } else {
                $error['provinsi'] = "Nama provinsi tidak ditemukan";
            }

            if ($error != NULL) {
                return $this->fail($error, 400);
            } else {
                $last_id = $villagesModel->select('id')->where('district_id', $val_kecamatan['id'])->orderBy('id', 'DESC')->first()['id'];

                $data_save = [
                    'id' => $last_id + 1,
                    'district_id' => $val_kecamatan['id'],
                    'name' => strtoupper($desa),
                ];
                $villagesModel->insert($data_save);

                $response = [
                    'message' => "data berhasil ditambahkan"
                ];
                return $this->respondCreated($response);
            }
        }
    }

    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        $validation = \Config\Services::validation();

        $villagesModel = new $this->villages;
        $districtModel = new $this->district;
        $regenciesModel = new $this->regencies;
        $provincesModel = new $this->provinces;

        $provinsi = esc($this->request->getVar('provinsi'));
        $kabupaten = esc($this->request->getVar('kabupaten'));
        $kecamatan = esc($this->request->getVar('kecamatan'));
        $desa = esc($this->request->getVar('desa'));


        $data = [
            'provinsi' => $provinsi,
            'kabupaten' => $kabupaten,
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ];

        $rules = [
            'provinsi' => [
                'label' => 'provinsi',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
            'kabupaten' => [
                'label' => 'kabupaten',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
            'kecamatan' => [
                'label' => 'kecamatan',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
            'desa' => [
                'label' => 'desa',
                'rules' => 'required',
                'errors' => [
                    'required' => 'nama {field} harus diisi'
                ]
            ],
        ];

        $validation->setRules($rules);

        if (!$validation->run($data)) {
            $result = $validation->getErrors();

            return $this->fail($result, 400);
        }

        if ($validation->run($data)) {
            $kab = '';
            $kot = '';
            if (stripos($kabupaten, 'kabupaten') === false && stripos($kabupaten, 'kota') === false) {
                $kab = 'kabupaten ' . $kabupaten;
                $kot = 'kota ' . $kabupaten;
            }
            $error = [];

            $val_provinsi = $provincesModel->select()->where('name', $provinsi)->first();

            if ($val_provinsi) {

                $val_kabupaten = $regenciesModel->select()->where('province_id', $val_provinsi['id'])->where('name', $kabupaten)->first();

                if (!$val_kabupaten) {
                    if ($kab) {
                        $val_kabupaten = $regenciesModel->select()->where('province_id', $val_provinsi['id'])->where('name', $kab)->first();
                    }
                    if (!$val_kabupaten) {
                        if ($kot) {
                            $val_kabupaten = $regenciesModel->select()->where('province_id', $val_provinsi['id'])->where('name', $kot)->first();
                        }
                    }
                }

                if ($val_kabupaten) {
                    $val_kecamatan = $districtModel->select()->where('regency_id', $val_kabupaten['id'])->where('name', $kecamatan)->first();

                    if ($val_kecamatan) {
                        $val_desa = $villagesModel->select()->where('district_id', $val_kecamatan['id'])->where('name', $desa)->first();

                        if ($val_desa) {
                            $error['desa'] = "nama desa telah terdaftar";
                        }
                    } else {
                        $error['kecamatan'] = "Nama kecamatan tidak ditemukan";
                    }
                } else {
                    $error['kabupaten'] = "Nama kabupaten tidak ditemukan";
                }
            } else {
                $error['provinsi'] = "Nama provinsi tidak ditemukan";
            }

            if ($error != NULL) {
                return $this->fail($error, 400);
            } else {
                $data_save = [
                    'id' => $id,
                    'district_id' => $val_kecamatan['id'],
                    'name' => strtoupper($desa),
                ];

                $villagesModel->update($id, $data_save);

                $response = [
                    'message' => "data berhasil diperbarui"
                ];
                return $this->respondCreated($response);
            }
        }
    }

    /**
     * Delete the designated resource object from the model.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        $villagesModel = new $this->villages;

        $villagesModel->delete($id);

        $response = [
            'message' => 'data berhasil dihapus'
        ];

        return $this->respondDeleted($response);
    }
}
