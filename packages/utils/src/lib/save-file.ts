const saveFile = ( content: string, fileName: string, contentType: string ) => {
	const a = document.createElement( 'a' );
	const file = new Blob( [ content ], { type: contentType } );
	a.href = URL.createObjectURL( file );
	a.download = fileName;
	a.click();
};

export default saveFile;
