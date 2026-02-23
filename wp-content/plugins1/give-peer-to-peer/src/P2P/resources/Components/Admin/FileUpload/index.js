import { useState } from 'react';
import classNames from 'classnames';

import styles from './style.module.scss';

const { __ } = wp.i18n;

const FileUpload = ( { children, onChange, onDrop, primaryColor, isMulti, ...rest } ) => {
	const [ isDragOver, setDragOver ] = useState( false );

	const handleDrop = ( e ) => {
		setDragOver( false );
		onDrop( e );
	};

	const getDropZoneBorderStyle = () => {
		if ( isDragOver ) {
			return {
				borderColor: primaryColor
			}
		}

		return null;
	};

	const handleSelectedFiles = ( e ) => onChange( e.target.files );

	return (
		<div
			className={ styles.dropZone }
			onDrop={ handleDrop }
			onDragOver={ ( e ) => {
				e.preventDefault();
				setDragOver( true );
			} }
			onDragLeave={ () => setDragOver( false ) }
			style={ getDropZoneBorderStyle() }
		>
			<label className={ classNames( styles.label, { [ styles.over ]: isDragOver } ) }>

				<div className={ styles.uploadDescription }>
					{ children }
				</div>

				<input
					type="file"
					className={ styles.input }
					multiple={ isMulti }
					onChange={ handleSelectedFiles }
					{ ...rest }
				/>
			</label>
		</div>
	);
};

export default FileUpload;
