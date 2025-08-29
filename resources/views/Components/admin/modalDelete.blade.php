<div id="modalDelete" onclick="closeModalDelete()">
    <form id="deleteForm">
        <input type="hidden" name="uuid" id="inpID" class="form-control">
        <h1>Konfirmasi Hapus {{ ucwords($modalDelete) }}</h1>
        <i class="fa-solid fa-xmark" onclick="closeModalDelete()"></i>
        <p>Apakah Anda yakin ingin menghapus {{ $modalDelete }} ini?</p>
        <div>
            <button type="button" onclick="closeModalDelete()">Batal</button>
            <button type="submit">Hapus</button>
        </div>
    </form>
</div>