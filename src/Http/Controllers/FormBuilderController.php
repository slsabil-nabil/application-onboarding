<?php

namespace Slsabil\ApplicationOnboarding\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Slsabil\ApplicationOnboarding\Models\FormField;
use Slsabil\ApplicationOnboarding\Models\BusinessApplication;

class FormBuilderController extends Controller
{
    /**
     * عرض صفحة إدارة الفورم
     */
    public function index()
    {
        $fields = FormField::orderBy('order')->get();

        // جدول الطلبات داخل الباكج
        $table = (new BusinessApplication)->getTable();

        // جميع أعمدة الجدول
        $columns = Schema::getColumnListing($table);

        // أعمدة لا يمكن ربط الحقول بها
        $excluded = [
            'id','form_data','status','created_at','updated_at',
            'rejection_reason','interpolation','interpolation_required_docs',
            'interpolation_contact_corrections','interpolation_uploaded_files',
            'resubmit_token','resubmit_expires_at'
        ];

        $mappableColumns = array_values(array_diff($columns, $excluded));

        // meta لكل عمود (نوعه)
        $columnMeta = [];
        foreach ($mappableColumns as $col) {
            $type = DB::selectOne("
                SELECT data_type FROM information_schema.columns
                WHERE table_name = ? AND column_name = ?
            ", [$table, $col]);

            $columnMeta[$col] = $type->data_type ?? 'string';
        }

        return view('application-onboarding::superadmin.form-builder', [
            'fields'          => $fields,
            'mappableColumns' => $mappableColumns,
            'columnMeta'      => $columnMeta,
        ]);
    }

    /**
     * حفظ حقل جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'label'          => 'required|string|max:255',
            'type'           => 'required|in:text,email,tel,textarea,file,list,heading',
            'name'           => 'nullable|alpha_dash|unique:form_fields,name',
            'is_required'    => 'nullable|boolean',
            'maps_to_column' => 'nullable|string|max:255',
            'options'        => 'nullable|string',
        ]);

        $order = FormField::max('order') + 1;

        $name = $request->filled('name')
            ? $request->name
            : \Str::slug($request->label, '_') . '_' . time();

        $options = null;
        if ($request->filled('options')) {
            $opts = explode(',', $request->options);
            $clean = [];
            foreach ($opts as $op) {
                $val = trim($op);
                $key = strtolower(str_replace(' ', '_', $val));
                $clean[$key] = $val;
            }
            $options = $clean;
        }

        FormField::create([
            'label'          => $request->label,
            'name'           => $name,
            'type'           => $request->type,
            'maps_to_column' => $request->maps_to_column,
            'is_required'    => $request->has('is_required'),
            'options'        => $options,
            'order'          => $order,
        ]);

        return back()->with('success', 'Field added successfully.');
    }

    /**
     * صفحة تعديل الحقل
     */
    public function edit(FormField $field)
    {
        $table = (new BusinessApplication)->getTable();
        $columns = Schema::getColumnListing($table);

        $excluded = [
            'id','form_data','status','created_at','updated_at','rejection_reason',
            'interpolation','interpolation_required_docs','interpolation_contact_corrections',
            'interpolation_uploaded_files','resubmit_token','resubmit_expires_at'
        ];

        $mappableColumns = array_values(array_diff($columns, $excluded));

        $columnMeta = [];
        foreach ($mappableColumns as $col) {
            $columnMeta[$col] = Schema::getColumnType($table, $col);
        }

        return view('application-onboarding::superadmin.edit-field', [
            'field'           => $field,
            'mappableColumns' => $mappableColumns,
            'columnMeta'      => $columnMeta,
        ]);
    }

    /**
     * تحديث الحقل
     */
    public function update(Request $request, FormField $field)
    {
        $request->validate([
            'label'          => 'required|string|max:255',
            'type'           => 'required|in:text,email,tel,textarea,file,list,heading',
            'name'           => [
                'required_unless:type,heading',
                'nullable',
                'alpha_dash',
                Rule::unique('form_fields')->ignore($field->id)
            ],
            'maps_to_column' => 'nullable|string|max:255',
            'is_required'    => 'nullable|boolean',
            'options'        => 'nullable|string',
        ]);

        $options = null;
        if ($request->filled('options')) {
            $opts = explode(',', $request->options);
            $clean = [];
            foreach ($opts as $op) {
                $val = trim($op);
                $key = strtolower(str_replace(' ', '_', $val));
                $clean[$key] = $val;
            }
            $options = $clean;
        }

        $field->update([
            'label'          => $request->label,
            'type'           => $request->type,
            'name'           => $request->name,
            'maps_to_column' => $request->maps_to_column,
            'is_required'    => $request->has('is_required'),
            'options'        => $options,
        ]);

        return redirect()->route('superadmin.form-builder.index')
            ->with('success', 'Field updated successfully.');
    }

    /**
     * حذف الحقل
     */
    public function destroy(FormField $field)
    {
        $field->delete();
        return back()->with('success', 'Field deleted successfully.');
    }

    /**
     * إعادة ترتيب الحقول (SortableJS)
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:form_fields,id'
        ]);

        foreach ($request->order as $index => $id) {
            FormField::where('id', $id)->update(['order' => $index]);
        }

        return response()->json(['status' => 'success']);
    }
}
