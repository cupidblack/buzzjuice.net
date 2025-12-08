import React from 'react'
import PropTypes from 'prop-types'
import { InfoIcon } from "@p2p/Components/Icons";

import styles from './style.module.scss';

const types = {
	warning: { color: '#FFA101' },
}

const Notice = ({ type, children }) => {
	return (
		<div className={ styles.container } style={{ borderColor: types[ type ].color }}>
			<InfoIcon height={ 24 } width={ 24 } style={{ fill: types[ type ].color }} />
			<span>
				{ children }
			</span>
		</div>
	)
}

Notice.props = {
	type: PropTypes.oneOf( Object.keys( types ) ).isRequired,
}

export default Notice
